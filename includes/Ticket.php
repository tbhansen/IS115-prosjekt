<?php
/**
 * Ticket Class
 * 
 * Purpose:
 * Represents a support ticket as an object instead of just a raw database array.
 */
class Ticket
{
    public int $id;
    public string $title;
    public string $description;
    public string $status;
    public string $priority;
    public ?string $customer_name;
    public ?string $customer_email;
    public ?string $created_at;
    public ?string $assigned_to_name;

    /**
     * Constructor: Initializes ticket properties from a database array.
     */
    public function __construct(array $data = [])
    {
        $this->id = (int)($data['id'] ?? 0);
        $this->title = (string)($data['title'] ?? '');
        $this->description = (string)($data['description'] ?? '');
        $this->status = (string)($data['status'] ?? 'open');
        $this->priority = (string)($data['priority'] ?? 'medium');
        $this->customer_name = $data['customer_name'] ?? null;
        $this->customer_email = $data['customer_email'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->assigned_to_name = $data['assigned_to_name'] ?? null;
    }

    /**
     * Checks if the ticket is closed.
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Returns an HTML status badge for display.
     */
    public function getStatusBadge(): string
    {
        return render_status_badge($this->status);
    }

    /**
     * Returns a human-readable creation date.
     */
    public function getFormattedDate(): string
    {
        return format_datetime($this->created_at);
    }
}
