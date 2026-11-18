<?php
/**
 * User Class
 * 
 * Purpose:
 * Represents a system user or staff member as an object.
 */
class User
{
    public int $id;
    public string $username;
    public string $name;
    public string $email;
    public string $role;

    /**
     * Constructor: Initializes user properties from a database array.
     */
    public function __construct(array $data = [])
    {
        $this->id = (int)($data['id'] ?? 0);
        $this->username = (string)($data['username'] ?? '');
        $this->name = (string)($data['name'] ?? '');
        $this->email = (string)($data['email'] ?? '');
        $this->role = (string)($data['role'] ?? 'staff');
    }

    /**
     * Returns the user's full name, or username if name is empty.
     */
    public function getDisplayName(): string
    {
        return !empty($this->name) ? $this->name : $this->username;
    }

    /**
     * Checks if the user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
