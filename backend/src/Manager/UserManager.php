<?php

namespace App\Manager;

use App\Entity\User;
use App\Util\VisitorInfoUtil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Class UserManager
 *
 * Manages user-related operations such as updating user data on login.
 *
 * @package App\Manager
 */
class UserManager
{
    private LogManager $logManager;
    private ErrorManager $errorManager;
    private VisitorInfoUtil $visitorInfoUtil;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasherInterface;

    public function __construct(
        LogManager $logManager,
        ErrorManager $errorManager,
        VisitorInfoUtil $visitorInfoUtil,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasherInterface
    ) {
        $this->logManager = $logManager;
        $this->errorManager = $errorManager;
        $this->entityManager = $entityManager;
        $this->visitorInfoUtil = $visitorInfoUtil;
        $this->passwordHasherInterface = $passwordHasherInterface;
    }

    /**
     * Gets the user repository for the given username.
     *
     * @param string $username The username to retrieve the repository for
     *
     * @return object|null The user repository if found, otherwise null
     */
    public function getUserRepo(string $username): ?object
    {
        // get user repo
        return $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
    }

    /**
     * Retrieves a user entity by their IP address.
     *
     * @param string $ipAddress The IP address of the user.
     * @return object|null The user entity if found, or null if not found.
     */
    public function getUserRepoByIP(string $ipAddress): ?object
    {
        // get user repo
        return $this->entityManager->getRepository(User::class)->findOneBy(['ip_address' => $ipAddress]);
    }

    /**
     * Updates user data on login.
     *
     * Finds the user by username and updates the last login time and IP address.
     *
     * @param string $identifier The username or identifier of the user
     *
     * @return void
     *
     * @throws \Exception If there is an error while updating user data
     */
    public function updateUserDataOnLogin(string $identifier): void
    {
        // get user repo
        $user = $this->getUserRepo($identifier);

        // check if user is found
        if ($user != null) {
            try {
                // set new data
                $user->setLastLoginTime(date('d.m.Y H:i:s'));
                $user->setIpAddress($this->visitorInfoUtil->getIP());

                // flush user data
                $this->entityManager->flush();
            } catch (\Exception $e) {
                $this->errorManager->handleError('error to update user data with login: ' . $e->getMessage(), JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }

    /**
     * Registers a new user.
     *
     * @param string $username The username of the new user
     * @param string $password The password of the new user
     *
     * @return void
     *
     * @throws \Exception If there is an error while registering the user
     */
    public function registerUser(string $username, string $password): void
    {
        // check if user exist
        if ($this->getUserRepo($username) == null) {
            try {
                // init user entity
                $user = new User();

                // hash password
                $password = $this->passwordHasherInterface->hashPassword($user, $password);

                // set user property
                $user->setUsername($username);
                $user->setPassword($password);
                $user->setRoles(['ROLE_USER']);
                $user->setRegisterTime(date('d.m.Y H:i:s'));
                $user->setLastLoginTime('non-logged');
                $user->setIpAddress('non-logged');

                // flush user to database
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                // log action
                $this->logManager->log('authenticator', 'new registration user: ' . $username);
            } catch (\Exception $e) {
                $this->errorManager->handleError('error to register new user: ' . $e->getMessage(), JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }

    /**
     * Checks if the specified user has the admin role.
     *
     * @param string $username The username of the user to check
     *
     * @return bool True if the user has the admin role, otherwise false
     */
    public function isUserAdmin(string $username): bool
    {
        $user = $this->getUserRepo($username);

        if ($user !== null) {
            $roles = $user->getRoles();
            return in_array('ROLE_ADMIN', $roles);
        }

        return false;
    }

    /**
     * Adds the admin role to a user.
     *
     * @param string $username The username of the user to add the admin role to
     *
     * @return void
     *
     * @throws \Exception If there is an error while adding the admin role
     */
    public function addAdminRoleToUser(string $username): void
    {
        // check if user exist
        if ($this->getUserRepo($username) != null) {
            try {
                // get user repo
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);

                // update role
                $user->setRoles(['ROLE_ADMIN']);

                // flush updated user data
                $this->entityManager->flush();

                // log action
                $this->logManager->log('role-granted', 'role admin granted to user: ' . $username);
            } catch (\Exception $e) {
                $this->errorManager->handleError('error to grant admin permissions: ' . $e->getMessage(), JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }

    /**
     * Retrieves user data based on the provided security context.
     *
     * This method retrieves user data using the provided security context.
     *
     * @param Security $security The security service providing the context for the user.
     * @return object The user data object.
     */
    public function getUserData(Security $security): object
    {
        return $this->getUserRepo($security->getUser()->getUserIdentifier());
    }
}
