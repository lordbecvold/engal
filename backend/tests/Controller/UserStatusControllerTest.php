<?php

namespace App\Tests\Controller;

use App\Tests\CustomCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class UserStatusControllerTest
 *
 * Unit test case for the UserStatusController class.
 *
 * @package App\Tests\Controller
 */
class UserStatusControllerTest extends CustomCase
{
    /**
     * Instance for making requests.
     */
    private KernelBrowser $client;

    /**
     * Set up before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->client = static::createClient();
        parent::setUp();
    }

    /**
     * Tests the getUserStatus endpoint.
     *
     * This method tests the behavior of the getUserStatus endpoint by sending a GET request
     * and asserting the response status code and content.
     *
     * @return void
     */
    public function testGetUserStatus(): void
    {
        // simulate user authentication
        $this->simulateUserAuthentication($this->client);

        // GET request to the API endpoint
        $this->client->request('GET', '/api/user/status');

        // decoding the content of the JsonResponse
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        // check response
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_OK);
        $this->assertSame(200, $responseData['code']);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals('test', $responseData['user_status']['username']);
        $this->assertIsArray($responseData['user_status']['roles']);
    }

    /**
     * Test retrieving user status when the user is not authenticated.
     *
     * @return void
     */
    public function testGetUserStatusNonAuth(): void
    {
        // GET request to the API endpoint
        $this->client->request('GET', '/api/user/status');

        // decoding the content of the JsonResponse
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        // check response
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_UNAUTHORIZED);
        $this->assertSame(401, $responseData['code']);
        $this->assertEquals('JWT Token not found', $responseData['message']);
    }
}
