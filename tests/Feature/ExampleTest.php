<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /** La raíz del sitio redirige al panel (que a su vez exige iniciar sesión). */
    public function test_la_raiz_redirige_al_panel(): void
    {
        $this->get('/')->assertRedirect('/panel');
    }
}
