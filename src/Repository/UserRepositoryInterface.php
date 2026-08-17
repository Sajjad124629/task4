<?php

namespace App\Repository;

use App\Entity\User;

interface UserRepositoryInterface
{
    /**
     * Get user data for DataTables
     *
     * @param array $requestParams
     * @return array
     */
    public function userData(array $requestParams): array;
}
