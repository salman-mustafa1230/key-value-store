<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class SwaggerUiTest extends TestCase
{
    public function test_swagger_ui_is_available(): void
    {
        $this->get('/swagger')
            ->assertOk()
            ->assertSee('swagger-ui', false);
    }
}
