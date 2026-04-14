<?php
// ============================================================
// includes/helpers.php  –  Utility & Security Helpers
// ============================================================

/**
 * Sanitise output to prevent XSS.
 * Always use this when echoing user-supplied data in HTML.
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Redirect to a URL and exit.
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Flash message helpers (stored in SESSION).
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Return a CSS badge class for complaint status values.
 */
function statusBadge(string $status): string
{
    return match ($status) {
        'open'      => 'badge-open',
        'in_review' => 'badge-review',
        'resolved'  => 'badge-resolved',
        'closed'    => 'badge-closed',
        default     => 'badge-open',
    };
}

/**
 * Human-readable status labels.
 */
function statusLabel(string $status): string
{
    return match ($status) {
        'open'      => 'Open',
        'in_review' => 'In Review',
        'resolved'  => 'Resolved',
        'closed'    => 'Closed',
        default     => ucfirst($status),
    };
}

/**
 * Format a datetime string for display.
 */
function formatDate(string $datetime): string
{
    return date('M j, Y  g:i A', strtotime($datetime));
}

/**
 * Validate that a string is not empty after trimming.
 */
function required(string $value): bool
{
    return trim($value) !== '';
}

/**
 * Validate an email address.
 */
function validEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
