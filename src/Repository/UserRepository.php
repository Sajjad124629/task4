<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface, UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    private function timeAgo(\DateTimeInterface $datetime): string
    {
        $now = new \DateTime();
        $diff = $now->diff($datetime);
        if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
        if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
        if ($diff->d > 0) {
            if ($diff->d >= 7) {
                $weeks = floor($diff->d / 7);
                return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
            }
            return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        }
        if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        
        return 'less than a minute ago';
    }

    public function userData(array $requestParams): array
    {

        $totalData = $this->count([]);
        $totalFiltered = $totalData;

        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.lastSeens', 'ls')
            ->addSelect('ls');
            
        $searchValue = trim($requestParams['search']['value'] ?? '');
        if ($searchValue !== '') {
            $qb->andWhere('u.fullname LIKE :search OR u.email LIKE :search OR u.status LIKE :search OR u.address LIKE :search')
                ->setParameter('search', '%' . $searchValue . '%');

            $totalFiltered = (clone $qb)->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();
        }

        $columns = [
            0 => 'u.id',
            1 => 'u.fullname',
            2 => 'u.email',
            3 => 'u.status',
            4 => 'u.lastSeen',
        ];

        $orderColumnIndex = $requestParams['order'][0]['column'] ?? 4;
        $orderColumn = $columns[$orderColumnIndex] ?? 'u.lastSeen';
        $orderDir = strtolower($requestParams['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
        $qb->orderBy($orderColumn, $orderDir);

        $limit = intval($requestParams['length'] ?? 10);
        $start = intval($requestParams['start'] ?? 0);

        if ($limit > 0) {
            $qb->setFirstResult($start)->setMaxResults($limit);
        }

        $paginator = new Paginator($qb, true);
        $users = iterator_to_array($paginator);

        $data = [];
        foreach ($users as $key => $user) {
            $address = $user->getAddress() ?? 'N/A';

            $statusBadge = match ($user->getStatus()) {
                User::STATUS_ACTIVE => '<span class="badge bg-success">Active</span>',
                User::STATUS_BLOCKED => '<span class="badge bg-danger">Blocked</span>',
                default => '<span class="badge bg-warning text-dark">Unverified</span>',
            };

            $lastSeensCollection = $user->getLastSeens()->slice(0, 8);
            $historyTimestamps = array_map(fn($ls) => $ls->getSeenAt()->getTimestamp(), $lastSeensCollection);
            $userHistory = array_reverse($historyTimestamps);

            $data[] = [
                'id' => $user->getId(),
                'key' => $key,
                'fullname' => '<div>' . $user->getFullname() . '</div><div class="small text-muted">' . $address . '</div>',
                'email' => $user->getEmail(),
                'status' => $statusBadge,
                'last_seen' => $user->getLastSeen() ? [
                    'absolute' => $user->getLastSeen()->format('F j, Y H:i:s'),
                    'relative' => $this->timeAgo($user->getLastSeen()),
                    'timestamp' => $user->getLastSeen()->getTimestamp(),
                    'history' => $userHistory
                ] : null,
            ];
        }

        return [
            'draw' => intval($requestParams['draw'] ?? 0),
            'recordsTotal' => intval($totalData),
            'recordsFiltered' => intval($totalFiltered),
            'data' => $data,
        ];
    }
}
