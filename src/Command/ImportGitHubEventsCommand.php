<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Actor;
use App\Entity\Event;
use App\Entity\Repo;
use App\Entity\EventType;
use App\Service\GuzzleClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Psr\Log\LoggerInterface;

class ImportGitHubEventsCommand extends Command
{
    protected static $defaultName = 'app:import-github-events';

    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private GuzzleClient $client;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger, GuzzleClient $client)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->client = $client;
    }

    protected function configure(): void
    {
        $this->setDescription('Import GitHub events from GHArchive');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '-1');

        $response = $this->client->get('/2023-01-01-0.json.gz');
        if ($response->getStatusCode() !== 200) {
            $output->writeln('Failed to fetch data from GHArchive.');
            return Command::FAILURE;
        }

        $responseData = $response->getBody()->getContents();
        $jsonContent = gzdecode($responseData);

        if ($jsonContent === false) {
            $output->writeln('Failed to decompress data.');
            return Command::FAILURE;
        }

        $lines = explode("\n", $jsonContent);
        $totalLines = count($lines);

        $progressBar = new ProgressBar($output, $totalLines);
        $progressBar->start();

        foreach ($this->getEventLines($lines) as $eventData) {
            try {
                $mappedType = $this->mapEventType($eventData['type']);
                if ($mappedType === null) {
                    $this->logger->warning("Unsupported event type: {$eventData['type']}");
                    $progressBar->advance();
                    continue;
                }
                $eventData['type'] = $mappedType;

                if ($this->eventExists((int) $eventData['id'])) {
                    $this->logger->info("Event with ID {$eventData['id']} already exists. Skipping.");
                    $progressBar->advance();
                    continue;
                }

                $actor = $this->getOrCreateActor($eventData['actor']);
                $repo = $this->getOrCreateRepo($eventData['repo']);

                $event = Event::fromArray($eventData, $actor, $repo);
                $this->entityManager->persist($event);

            } catch (\Exception $e) {
                $this->logger->error('Failed to process event: ' . $e->getMessage());
            }
            $progressBar->advance();
        }

        $this->entityManager->flush();
        $progressBar->finish();

        $output->writeln("\nData successfully fetched and saved.");
        return Command::SUCCESS;
    }

    private function getEventLines(array $lines): \Generator
    {
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $eventData = json_decode($line, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->warning('Failed to decode JSON line: ' . json_last_error_msg());
                continue;
            }
            yield $eventData;
        }
    }

    private function getOrCreateActor(array $actorData): Actor
    {
        $actor = $this->entityManager->getRepository(Actor::class)->find($actorData['id']);
        if (!$actor) {
            $actor = Actor::fromArray($actorData);
            $this->entityManager->persist($actor);
        }
        return $actor;
    }

    private function getOrCreateRepo(array $repoData): Repo
    {
        $repo = $this->entityManager->getRepository(Repo::class)->find($repoData['id']);
        if (!$repo) {
            $repo = Repo::fromArray($repoData);
            $this->entityManager->persist($repo);
        }
        return $repo;
    }

    private function eventExists(int $eventId): bool
    {
        return (bool) $this->entityManager->getRepository(Event::class)->find($eventId);
    }

    private function mapEventType(string $apiType): ?string
    {
        $mapping = [
            'PushEvent' => EventType::PUSH_EVENT,
            'PullRequestEvent' => EventType::PULL_REQUEST_EVENT,
            'PullRequestReviewEvent' => EventType::PULL_REQUEST_REVIEW_EVENT,
            'CreateEvent' => EventType::CREATE_EVENT,
            'DeleteEvent' => EventType::DELETE_EVENT,
            'IssueCommentEvent' => EventType::ISSUE_COMMENT_EVENT,
            'IssuesEvent' => EventType::ISSUE_EVENT,
            'ForkEvent' => EventType::FORK_EVENT,
            'WatchEvent' => EventType::WATCH_EVENT,
            'ReleaseEvent' => EventType::RELEASE_EVENT,
            'CommitCommentEvent' => EventType::COMMIT_COMMENT_EVENT,
            'GollumEvent' => EventType::GOLLUM_EVENT,
            'PullRequestReviewCommentEvent' => EventType::PULL_REQUEST_REVIEW_COMMENT_EVENT,
            'MemberEvent' => EventType::MEMBER_EVENT,
            'PublicEvent' => EventType::PUBLIC_EVENT,
        ];

        return $mapping[$apiType] ?? null;
    }
}
