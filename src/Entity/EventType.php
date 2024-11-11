<?php

namespace App\Entity;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

class EventType extends AbstractEnumType
{
    public const COMMIT = 'COM';
    public const COMMENT = 'MSG';
    public const PULL_REQUEST = 'PR';
    public const PUSH_EVENT = 'PUSH';
    public const PULL_REQUEST_EVENT = 'PULL_REQUEST_EVENT';
    public const PULL_REQUEST_REVIEW_EVENT = 'PULL_REQUEST_REVIEW_EVENT';
    public const CREATE_EVENT = 'CREATE';
    public const DELETE_EVENT = 'DELETE';
    public const ISSUE_COMMENT_EVENT = 'ISSUE_COMMENT';
    public const ISSUE_EVENT = 'ISSUE_EVENT';
    public const FORK_EVENT = 'FORK';
    public const WATCH_EVENT = 'WATCH';
    public const RELEASE_EVENT = 'RELEASE';
    public const COMMIT_COMMENT_EVENT = 'COMMIT_COMMENT';
    public const GOLLUM_EVENT = 'GOLLUM';
    public const PULL_REQUEST_REVIEW_COMMENT_EVENT = 'PULL_REQUEST_REVIEW_COMMENT';
    public const MEMBER_EVENT = 'MEMBER';
    public const PUBLIC_EVENT = 'PUBLIC';

    protected static array $choices = [
        self::COMMIT => 'Commit',
        self::COMMENT => 'Comment',
        self::PULL_REQUEST => 'PullRequest',
        self::PUSH_EVENT => 'PushEvent',
        self::PULL_REQUEST_EVENT => 'PullRequestEvent',
        self::PULL_REQUEST_REVIEW_EVENT => 'PullRequestReviewEvent',
        self::CREATE_EVENT => 'CreateEvent',
        self::DELETE_EVENT => 'DeleteEvent',
        self::ISSUE_COMMENT_EVENT => 'IssueCommentEvent',
        self::ISSUE_EVENT => 'IssueEvent',
        self::FORK_EVENT => 'ForkEvent',
        self::WATCH_EVENT => 'WatchEvent',
        self::RELEASE_EVENT => 'ReleaseEvent',
        self::COMMIT_COMMENT_EVENT => 'CommitCommentEvent',
        self::GOLLUM_EVENT => 'GollumEvent',
        self::PULL_REQUEST_REVIEW_COMMENT_EVENT => 'PullRequestReviewCommentEvent',
        self::MEMBER_EVENT => 'MemberEvent',
        self::PUBLIC_EVENT => 'PublicEvent',
    ];
}
