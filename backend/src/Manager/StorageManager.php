<?php

namespace App\Manager;

use App\Entity\Media;
use App\Repository\MediaRepository;
use Symfony\Component\String\ByteString;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class StorageManager
 *
 * StorageManager class for manipulate with media storage filesystem & database
 *
 * @package App\Manager
 */
class StorageManager
{
    private ErrorManager $errorManager;
    private MediaRepository $mediaRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(ErrorManager $errorManager, MediaRepository $mediaRepository, EntityManagerInterface $entityManager)
    {
        $this->errorManager = $errorManager;
        $this->entityManager = $entityManager;
        $this->mediaRepository = $mediaRepository;
    }

    /**
     * Retrieves a media entity from the repository based on the provided search criteria.
     *
     * @param array<mixed> $search An associative array representing the search criteria.
     *
     * @return object|null The found media entity or null if not found.
     */
    public function getMediaEntityRepository(array $search): ?object
    {
        return $this->entityManager->getRepository(Media::class)->findOneBy($search);
    }

    /**
     * Store media entity.
     *
     * @param array<string> $data
     *
     * @return string|null
     */
    public function storeMediaEntity(array $data): ?string
    {
        // init media entity
        $media = new Media();

        // generate entity token
        $token = ByteString::fromRandom(32)->toString();

        // check if token not exist
        if ($this->getMediaEntityRepository(['token' => $token]) != null) {
            $this->storeMediaEntity($data);
        }

        try {
            // get media name
            $name = pathinfo($data['name'], PATHINFO_FILENAME);

            // set entity data
            $media->setName($name);
            $media->setGalleryName($data['gallery_name']);
            $media->setType($data['type']);
            $media->setOwnerId(intval($data['owner_id']));
            $media->setToken($token);
            $media->setUploadTime($data['upload_time']);
            $media->setLastEditTime('non-edited');

            // store data to database
            $this->entityManager->persist($media);
            $this->entityManager->flush();

            return $token;
        } catch (\Exception $e) {
            $this->errorManager->handleError('error to store entity data: ' . $e->getMessage(), JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            return null;
        }
    }

    /**
     * Store media file.
     *
     * @param string $token
     * @param object $file
     * @param int $userId
     * @param string $fileType
     *
     * @return void
     */
    public function storeMediaFile(string $token, object $file, int $userId, string $fileType = 'videos'): void
    {
        // get uploaded file extension
        $fileExtension = $file->getClientOriginalExtension();

        try {
            // check file type
            if (str_contains($file->getClientMimeType(), 'image')) {
                $fileType = 'photos';
            }

            // move file to final storage directory
            $file->move(__DIR__ . '/../../storage/' . $_ENV['APP_ENV'] . '/' . $userId . '/' . $fileType, $token . '.' . $fileExtension);
        } catch (\Exception $e) {
            $this->errorManager->handleError('error to store media file: ' . $e->getMessage(), JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Retrieves the list of gallery names associated with a specific user ID.
     *
     * @param int $userId The ID of the user whose gallery names are to be retrieved.
     *
     * @return array<int<0,max>,array<string,string|null>> The array containing the gallery names.
     */
    public function getGalleryListByUserId(int $userId): array
    {
        $galleryNamesArray = [];

        // get gallery names
        $galleryNames = $this->mediaRepository->findDistinctGalleryNamesByUserId($userId);

        // build gallery list array
        foreach ($galleryNames as $name) {
            // get gallery name
            $name = $name['gallery_name'];

            $galleryNamesArray[] = [
                'name' => $name,
                'first_token' => $this->mediaRepository->findFirstTokenByProperty($userId, $name)
            ];
        }

        return $galleryNamesArray;
    }

    /**
     * Checks if media with the given token and owner ID exists.
     *
     * @param int $ownerId The ID of the owner of the media.
     * @param string $mediaToken The token of the media to check.
     * @param bool $canCrash The specify if this process can crash without valid response.
     *
     * @return bool True if the media exists for the given owner, false otherwise.
     */
    public function isMediaExist(int $ownerId, string $mediaToken, bool $canCrash = true): bool
    {
        // check if entity exist in database
        if ($this->getMediaEntityRepository(['token' => $mediaToken]) != null) {
            // check if media file exist
            if ($this->getMediaFile($ownerId, $mediaToken, $canCrash) != null) {
                return true;
            } else {
                // check if process can crash before complete
                if ($canCrash) {
                    $this->errorManager->handleError('error to get media: ' . $mediaToken . ' but entity exist', 404);
                }
                return false;
            }
        }

        return false;
    }

    /**
     * Retrieves the media type associated with the provided media token.
     *
     * @param string $mediaToken The token associated with the media.
     *
     * @return string|null The media type if found, otherwise null.
     */
    public function getMediaType(string $mediaToken): ?string
    {
        return $this->getMediaEntityRepository(['token' => $mediaToken])->getType();
    }

    /**
     * Retrieves the path of the media file associated with the given user ID and token.
     *
     * @param int $userId The ID of the user.
     * @param string $token The token associated with the media file.
     * @param bool $canCrash The specify if this process can crash without valid response.
     *
     * @return string|null The path of the media file, or null if not found.
     */
    public function getMediaFile(int $userId, string $token, bool $canCrash = true): ?string
    {
        // build media file path pathern
        $mediaPathPathern = __DIR__ . '/../../storage/' . $_ENV['APP_ENV'] . '/' . $userId . '/*/' . $token . '.*';

        // get files in pathern
        $files = glob($mediaPathPathern);

        // check if media file found
        if ($files !== false && count($files) > 0) {
            return $files[0];
        } else {
            // check if process can crash before complete
            if ($canCrash) {
                $this->errorManager->handleError('error to found media file: ' . $userId . ':' . $token, 404);
            }
        }

        return null;
    }

    /**
     * Retrieves the content of the media file associated with the given user ID and token.
     *
     * @param int $userId The ID of the user.
     * @param string $token The token associated with the media file.
     *
     * @return string|null The content of the media file, or null if not found.
     */
    public function getMediaContent(int $userId, string $token): ?string
    {
        // get media file
        $file = $this->getMediaFile($userId, $token);

        // return file content
        return file_get_contents($file);
    }

    /**
     * Retrieve information about all media files.
     *
     * This method retrieves information about all media files stored in the system.
     *
     * @return array<int<0,max>,array<string,string>> An array containing information about each media file. Each element of the array is an associative array with the following keys:
     *   - 'folder' (string): The folder where the media file is stored.
     *   - 'user_id' (string): The ID of the user associated with the media file.
     *   - 'token' (string): The token identifying the media file, without the file extension.
     */
    public function getAllMediaFiles(): array
    {
        // build media file path pattern
        $mediaPathPattern = __DIR__ . '/../../storage/' . $_ENV['APP_ENV'] . '/*/*/*';

        // get files in pattern
        $files = glob($mediaPathPattern);

        // initialize an empty array to store the extracted information
        $result = [];

        // iterate through each file path
        foreach ($files as $file) {
            // extract token, folder, and user ID using regular expressions
            preg_match('/\/([^\/]+)\/([^\/]+)\/([^\/]+)$/', $file, $matches);

            // check if the regex match was successful
            if (isset($matches[1]) && isset($matches[2]) && isset($matches[3])) {
                // remove extension from token
                $token = pathinfo($matches[3], PATHINFO_FILENAME);

                // add the extracted information to the result array
                $result[] = [
                    'folder' => $matches[2],
                    'user_id' => $matches[1],
                    'token' => $token
                ];
            }
        }

        return $result;
    }
}
