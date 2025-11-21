<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\SecurityLog;
use App\Models\BlockedIp;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SecuritySystemTest extends TestCase
{
    /**
     * Test: Détection d'injection SQL
     */
    public function test_sql_injection_detection()
    {
        $response = $this->get('/login?username=admin\' OR \'1\'=\'1');

        // Vérifier qu'un log a été créé
        $this->assertDatabaseHas('security_logs', [
            'type' => SecurityLog::TYPE_SQL_INJECTION,
            'severity' => SecurityLog::SEVERITY_CRITICAL,
        ]);
    }

    /**
     * Test: Détection XSS
     */
    public function test_xss_detection()
    {
        $response = $this->post('/register', [
            'name' => '<script>alert("XSS")</script>',
            'email' => 'test@test.com',
            'password' => 'password123',
        ]);

        $this->assertDatabaseHas('security_logs', [
            'type' => SecurityLog::TYPE_XSS,
        ]);
    }

    /**
     * Test: Détection Path Traversal
     */
    public function test_path_traversal_detection()
    {
        $response = $this->get('/files?path=../../etc/passwd');

        $this->assertDatabaseHas('security_logs', [
            'type' => SecurityLog::TYPE_PATH_TRAVERSAL,
        ]);
    }

    /**
     * Test: Blocage automatique après plusieurs tentatives
     */
    public function test_auto_blocking_after_multiple_attempts()
    {
        $ip = '192.168.1.100';

        // Simuler 5 attaques depuis la même IP
        for ($i = 0; $i < 5; $i++) {
            SecurityLog::logAttack([
                'ip_address' => $ip,
                'type' => SecurityLog::TYPE_SQL_INJECTION,
                'severity' => SecurityLog::SEVERITY_HIGH,
                'method' => 'GET',
                'url' => '/test',
                'user_agent' => 'Test Agent',
                'description' => 'Test attack',
            ]);
        }

        // Vérifier que l'IP est bloquée
        $this->assertTrue(BlockedIp::isBlocked($ip));
    }

    /**
     * Test: Déblocage manuel d'une IP
     */
    public function test_manual_unblock_ip()
    {
        $ip = '192.168.1.200';

        // Bloquer l'IP
        BlockedIp::blockIp($ip, 'Test', 24);
        $this->assertTrue(BlockedIp::isBlocked($ip));

        // Débloquer l'IP
        BlockedIp::unblockIp($ip);
        $this->assertFalse(BlockedIp::isBlocked($ip));
    }

    /**
     * Test: Nettoyage des blocages expirés
     */
    public function test_clean_expired_blocks()
    {
        $ip = '192.168.1.300';

        // Créer un blocage déjà expiré
        BlockedIp::create([
            'ip_address' => $ip,
            'reason' => 'Test',
            'attempts' => 1,
            'blocked_until' => now()->subHour(),
            'is_permanent' => false,
        ]);

        // Nettoyer
        BlockedIp::cleanExpired();

        // Vérifier que le blocage a été supprimé
        $this->assertDatabaseMissing('blocked_ips', [
            'ip_address' => $ip,
        ]);
    }
}
