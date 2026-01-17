<?php

namespace App\Entity;

use App\Repository\LogEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogEventRepository::class)]
#[ORM\Index(columns: ['ip'], name: 'idx_log_ip')]
#[ORM\Index(columns: ['event_type'], name: 'idx_event_type')]
#[ORM\Index(columns: ['severity'], name: 'idx_severity')]
#[ORM\Index(columns: ['created_at'], name: 'idx_created_at')]
class LogEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // info | warning | error | critical (technique)
    #[ORM\Column(length: 20)]
    private ?string $level = null;

    // LOW | MEDIUM | HIGH | CRITICAL (SIEM / SOC)
    #[ORM\Column(length: 20)]
    private ?string $severity = 'LOW';

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\Column(length: 45)]
    private ?string $ip = null;

    #[ORM\Column(length: 255)]
    private ?string $route = null;

    #[ORM\Column(length: 10)]
    private ?string $method = null;

    // page_visit | login_failed | brute_force | scan | suspicious
    #[ORM\Column(length: 50)]
    private ?string $eventType = null;

    // Nombre de tentatives (brute force)
    #[ORM\Column(nullable: true)]
    private ?int $attemptCount = null;

    // new | analysed | ignored | blocked
    #[ORM\Column(length: 20)]
    private ?string $status = 'new';

    // User-Agent attaquant
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $userAgent = null;

    // Log marqué par l’analyste SOC
    #[ORM\Column(options: ['default' => false])]
    private bool $isFlagged = false;

    // Suppression logique (SIEM friendly)
    #[ORM\Column(options: ['default' => false])]
    private bool $isDeleted = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    /* ================= GETTERS / SETTERS ================= */

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(string $level): static
    {
        $this->level = $level;
        return $this;
    }

    public function getSeverity(): ?string
    {
        return $this->severity;
    }

    public function setSeverity(string $severity): static
    {
        $this->severity = $severity;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): static
    {
        $this->ip = $ip;
        return $this;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function setRoute(string $route): static
    {
        $this->route = $route;
        return $this;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(string $method): static
    {
        $this->method = $method;
        return $this;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): static
    {
        $this->eventType = $eventType;
        return $this;
    }

    public function getAttemptCount(): ?int
    {
        return $this->attemptCount;
    }

    public function setAttemptCount(?int $attemptCount): static
    {
        $this->attemptCount = $attemptCount;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function isFlagged(): bool
    {
        return $this->isFlagged;
    }

    public function setIsFlagged(bool $isFlagged): static
    {
        $this->isFlagged = $isFlagged;
        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): static
    {
        $this->isDeleted = $isDeleted;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
