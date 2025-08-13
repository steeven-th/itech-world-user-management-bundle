<?php

namespace ItechWorld\UserManagementBundle\Controller;

use ItechWorld\UserManagementBundle\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/username', name: 'api_username_')]
class UsernameController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    /**
     * Vérifie si un nom d'utilisateur est disponible
     * Ne retourne que true/false sans exposer de données utilisateur
     */
    #[Route('/available', name: 'available', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function checkAvailable(Request $request): JsonResponse
    {
        $username = $request->query->get('username');
        $currentUser = $this->getUser();

        if (!$username) {
            return $this->json([
                'available' => false,
                'error' => 'Le paramètre username est requis'
            ], 400);
        }

        // Si c'est le même username que l'utilisateur actuel, c'est disponible
        if ($currentUser && $currentUser->getUserIdentifier() === $username) {
            return $this->json([
                'available' => true,
                'current' => true
            ]);
        }

        // Validation de base
        if (strlen($username) < 3) {
            return $this->json([
                'available' => false,
                'error' => 'Le nom d\'utilisateur doit contenir au moins 3 caractères'
            ], 400);
        }

        // Vérifier si le username existe déjà
        $existingUser = $this->userRepository->findOneBy(['username' => $username]);

        return $this->json([
            'available' => $existingUser === null
        ]);
    }
}
