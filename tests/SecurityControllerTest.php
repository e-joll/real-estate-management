<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    public function testLoginPage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('button', 'Se connecter');
    }

    public function testLogoutPage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/logout');

        $this->assertResponseRedirects('/login');
    }

    public function testDirectorSuccessfulLogin()
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        // Filling out the form
        $client->submitForm('Se connecter', [
            '_username' => 'admin@rem.fr',
            '_password' => '123',
        ]);

        $client->followRedirect();

        // Check that you are connected correctly
        $this->assertResponseRedirects('/director');
    }

    public function testInvalidLogin()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        // Filling out the form
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'admin@rem.fr',
            '_password' => '1234',
        ]);

        $client->submit($form);
        $client->followRedirect();

        // Check that you are redirected to the login page
        $this->assertResponseIsSuccessful();
    }
}