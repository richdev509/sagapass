<?php

namespace Tests\Feature\FaceVerification;

use App\Http\Controllers\Public\FaceTestController;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * La page de test est publique (sans connexion) : elle doit être invisible tant
 * qu'elle n'est pas explicitement activée, et exiger le lien secret. Appel direct
 * du contrôleur (les middlewares globaux exigent d'autres tables).
 */
class FaceTestPageAccessTest extends TestCase
{
    private function assertNotFound(callable $call): void
    {
        try {
            $call();
            $this->fail('Un 404 était attendu.');
        } catch (HttpException $e) {
            $this->assertSame(404, $e->getStatusCode());
        }
    }

    public function test_the_page_is_hidden_when_disabled_even_with_the_right_token(): void
    {
        config(['faceverification.face_test' => ['enabled' => false, 'token' => 'secret-token']]);

        $this->assertNotFound(fn () => app(FaceTestController::class)->show('secret-token'));
    }

    public function test_the_page_is_hidden_when_no_token_is_configured(): void
    {
        config(['faceverification.face_test' => ['enabled' => true, 'token' => null]]);

        $this->assertNotFound(fn () => app(FaceTestController::class)->show(''));
    }

    public function test_a_wrong_token_is_a_404(): void
    {
        config(['faceverification.face_test' => ['enabled' => true, 'token' => 'secret-token']]);

        $this->assertNotFound(fn () => app(FaceTestController::class)->show('guess'));
    }

    public function test_the_right_token_shows_the_page_when_enabled(): void
    {
        config(['faceverification.face_test' => ['enabled' => true, 'token' => 'secret-token']]);

        $html = app(FaceTestController::class)->show('secret-token')->render();

        $this->assertStringContainsString('Test de reconnaissance faciale', $html);
        // @json échappe les slashes de l'URL du POST.
        $this->assertStringContainsString('face-test\/secret-token\/compare', $html);
    }
}
