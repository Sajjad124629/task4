<?php

namespace App\Controller;

use App\Entity\LastSeen;
use App\Entity\User;
use App\Repository\UserRepositoryInterface;
use App\Trait\JsonResponseTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Attribute\Auth;
use App\Service\UserAction\UserActionRegistry;

#[Route('/dashboard')]
#[Auth]
class DashboardController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route('/', name: 'app_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        // Update user's last seen time on dashboard load
        /** @var User $user */
        $user = $this->getUser();
        if ($user) {
            $now = new \DateTime();
            $user->setLastSeen($now);
            
            $lastSeenRecord = $this->entityManager->getRepository(LastSeen::class)->findOneBy(['user' => $user], ['seenAt' => 'DESC']);
            if (!$lastSeenRecord || $now->getTimestamp() - $lastSeenRecord->getSeenAt()->getTimestamp() > 1800) {
                $newLastSeen = new LastSeen();
                $newLastSeen->setUser($user);
                $newLastSeen->setSeenAt($now);
                $this->entityManager->persist($newLastSeen);
            }

            $this->entityManager->flush();
        }

        return $this->render('pages/dashboard/index.html.twig');
    }

    #[Route('/user-data', name: 'app_dashboard_user_data', methods: ['GET'])]
    public function userData(Request $request, UserRepositoryInterface $userRepository): Response
    {
        $requestParams = $request->query->all();
        $data = $userRepository->userData($requestParams);

        return $this->json($data);
    }

    #[Route('/user-action', name: 'app_dashboard_user_action', methods: ['POST'])]
    public function action(Request $request, UserActionRegistry $actionRegistry): Response
    {
        $actionName = $request->request->get('action');
        $ids = $request->request->all('ids');

        if (empty($ids) || !is_array($ids)) {
            return $this->jsonResponse('error', 'Please select at least one record!', 'Oops!');
        }

        $actionHandler = $actionRegistry->getAction($actionName);
        if (!$actionHandler) {
            return $this->jsonResponse('error', 'Invalid action specified.', 'Error');
        }

        $userRepository = $this->entityManager->getRepository(User::class);
        $users = $userRepository->findBy(['id' => $ids]);

        if (empty($users)) {
            return $this->jsonResponse('error', 'Users not found.', 'Error');
        }

        foreach ($users as $user) {
            $actionHandler->execute($user);
        }

        $this->entityManager->flush();

        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $selfAffected = false;

        if (in_array($currentUser->getId(), $ids) && $actionHandler->isSelfAffecting()) {
            $selfAffected = true;
        }

        return $this->jsonResponse(
            'success',
            'Action performed successfully.',
            'Success',
            200,
            ['self_affected' => $selfAffected]
        );
    }
}
