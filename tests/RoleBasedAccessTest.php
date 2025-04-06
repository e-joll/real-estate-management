<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RoleBasedAccessTest extends WebTestCase
{
    public function testDirectorAccessDeniedForAnonymousUser()
    {
        $client = static::createClient();
        $client->request('GET', '/director');

        // Checks that the non-logged in user is redirected to the login page
        $this->assertResponseRedirects('/login');
    }

    public function testAgentAccessDeniedForAnonymousUser()
    {
        $client = static::createClient();
        $client->request('GET', '/agent');

        // Checks that the non-logged in user is redirected to the login page
        $this->assertResponseRedirects('/login');
    }

    public function testCustomerAccessDeniedForAnonymousUser()
    {
        $client = static::createClient();
        $client->request('GET', '/customer');

        // Checks that the non-logged in user is redirected to the login page
        $this->assertResponseRedirects('/login');
    }

    public function testDirectorAccessGrantedForDirectorUser()
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        // Simulate a director login
        $client->submitForm('Se connecter', [
            '_username' => 'admin@rem.fr',
            '_password' => '123',
        ]);
        $client->followRedirect();

        // The director tries to access director dashboard
        $client->request('GET', '/director');

        // Check that he has access to the page
        $this->assertResponseIsSuccessful();
    }
}