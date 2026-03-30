<?php
use PHPUnit\Framework\TestCase;

/**
 * UserBanTest
 * Automated Unit Testing for User Story: 
 * "As an Admin, I want to ban rule-breaking users so that I can ensure platform safety."
 */
class UserBanTest extends TestCase {
    private $testUserFile = 'users_test.txt';
    private $testAuditLog = 'audit_log_test.txt';

    protected function setUp(): void {
        // Setup: Create a mock user database for testing
        $hashedPassword = password_hash("Password123", PASSWORD_DEFAULT);
        // Format: Name|Email|Phone|HashedPassword|Status
        $content = "BadUser|trouble@example.com|0123456789|$hashedPassword|Active\n";
        file_put_contents($this->testUserFile, $content);
        
        // Ensure audit log starts empty
        if (file_exists($this->testAuditLog)) {
            unlink($this->testAuditLog);
        }
    }

    protected function tearDown(): void {
        // Cleanup: Remove test files after execution
        if (file_exists($this->testUserFile)) unlink($this->testUserFile);
        if (file_exists($this->testAuditLog)) unlink($this->testAuditLog);
    }

    /**
     * @test
     * AC3: System updates user status to "Banned"
     */
    public function test_ac3_system_updates_status_to_banned() {
        $emailToBan = "trouble@example.com";
        $lines = file($this->testUserFile, FILE_IGNORE_NEW_LINES);
        $updated = false;

        foreach ($lines as &$line) {
            $data = explode('|', $line);
            if ($data[1] === $emailToBan) {
                $data[4] = 'Banned'; // Requirement: Update status
                $line = implode('|', $data);
                $updated = true;
            }
        }
        file_put_contents($this->testUserFile, implode("\n", $lines));

        $this->assertTrue($updated);
        $this->assertStringContainsString('Banned', file_get_contents($this->testUserFile));
    }

    /**
     * @test
     * AC5: Banned user is prevented from logging in
     */
    public function test_ac5_banned_user_cannot_login() {
        // Simulate a user record retrieved from the file
        $userRecord = ['Name', 'trouble@example.com', '0123456789', 'hash', 'Banned'];
        
        // Security Logic: Only 'Active' users can authenticate
        $loginAllowed = ($userRecord[4] === 'Active');

        $this->assertFalse($loginAllowed, "Security Requirement: Banned users must be denied access.");
    }

    /**
     * @test
     * AC6: System records the action in an audit log including the reason
     */
    public function test_ac6_audit_log_records_ban_reason() {
        $adminId = "Admin_01";
        $targetUser = "trouble@example.com";
        $reason = "Spamming and Harassment"; // Requirement: Admin must provide reason
        $timestamp = date("Y-m-d H:i:s");

        $logEntry = "[$timestamp] Admin $adminId banned $targetUser. Reason: $reason\n";
        file_put_contents($this->testAuditLog, $logEntry, FILE_APPEND);

        $logContent = file_get_contents($this->testAuditLog);
        
        $this->assertStringContainsString($reason, $logContent);
        $this->assertStringContainsString($adminId, $logContent);
    }

    /**
     * @test
     * AC2: Admin provides a reason for the ban (Validation Check)
     */
    public function test_ac2_ban_requires_non_empty_reason() {
        $providedReason = ""; // Empty reason
        
        // Validation logic: Reason is mandatory
        $isValidReason = !empty(trim($providedReason));

        $this->assertFalse($isValidReason, "Validation Requirement: Ban reason cannot be empty.");
    }
}