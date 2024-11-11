<?php

namespace App\Tests\Command;

use App\Command\ImportGitHubEventsCommand;
use App\Entity\Actor;
use App\Entity\Event;
use App\Entity\Repo;
use App\Service\GuzzleClient;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Output\OutputInterface;

class ImportGitHubEventsCommandTest extends TestCase
{
    private $entityManager;
    private $logger;
    private $client;
    private $commandTester;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->client = $this->createMock(GuzzleClient::class);

        $command = new ImportGitHubEventsCommand($this->entityManager, $this->logger, $this->client);
        $application = new Application();
        $application->add($command);
        $this->commandTester = new CommandTester($application->find('app:import-github-events'));
    }

    public function testExecuteWithSuccessfulResponse()
    {
        $this->client->method('get')->willReturn($this->createMockResponse());

        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $this->entityManager->method('getRepository')->willReturn($this->createMockRepository(false));

        $output = $this->commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $this->assertSame(0, $output);  // Command::SUCCESS
        $this->assertStringContainsString('Data successfully fetched and saved.', $this->commandTester->getDisplay());
    }

    public function testEventExistsSkipsDuplicate()
    {
        $this->client->method('get')->willReturn($this->createMockResponse());

        // Mock du repository pour simuler un événement existant en base
        $this->entityManager->method('getRepository')->willReturn($this->createMockRepository(true));

        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        $this->commandTester->execute([]);

        $this->assertStringContainsString('already exists. Skipping.', $this->commandTester->getDisplay());
    }

    public function testUnsupportedEventTypeLogsWarning()
    {
        $invalidEventResponse = $this->createMockResponse('{"id":"123", "type":"UnsupportedEvent"}');
        $this->client->method('get')->willReturn($invalidEventResponse);

        $this->logger->expects($this->once())->method('warning')->with($this->stringContains('Unsupported event type'));

        $this->commandTester->execute([]);

        $this->assertStringContainsString('Unsupported event type', $this->commandTester->getDisplay());
    }

    private function createMockResponse(string $body = null)
    {
        $mockResponse = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getBody')->willReturn($this->createStream($body));
        return $mockResponse;
    }

    private function createStream($body = null)
    {
        $stream = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        $stream->method('getContents')->willReturn(gzencode($body ?? $this->getMockJsonContent()));
        return $stream;
    }

    private function getMockJsonContent(): string
    {
        return json_encode([
            ["id" => "123", "type" => "PushEvent", "actor" => ["id" => "1", "login" => "test_user", "url" => "url", "avatar_url" => "avatar_url"], "repo" => ["id" => "1", "name" => "test_repo", "url" => "url"], "created_at" => "2023-01-01T00:00:00Z"]
        ]);
    }

    private function createMockRepository(bool $exists)
    {
        $repository = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
        $repository->method('find')->willReturn($exists ? $this->createConfiguredMock(Event::class, []) : null);
        return $repository;
    }
}
