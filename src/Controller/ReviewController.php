<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Review;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class ReviewController extends AbstractController
{
    #[Route('/reviews', name: 'api_reviews_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json([
                'error' => 'JSON inválido',
                'message' => json_last_error_msg()
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validar campos requeridos
        $errors = [];

        if (!isset($data['book_id'])) {
            $errors['book_id'] = 'El ID del libro es obligatorio';
        }

        if (!isset($data['rating'])) {
            $errors['rating'] = 'La calificación es obligatoria';
        } elseif (!is_int($data['rating']) || $data['rating'] < 1 || $data['rating'] > 5) {
            $errors['rating'] = 'La calificación debe ser un número entero entre 1 y 5';
        }

        if (!isset($data['comment']) || trim($data['comment']) === '') {
            $errors['comment'] = 'El comentario no puede estar vacío';
        }

        if (!empty($errors)) {
            return $this->json([
                'error' => 'Errores de validación',
                'violations' => $errors
            ], Response::HTTP_BAD_REQUEST);
        }

        // Verificar que el libro existe
        $book = $entityManager->getRepository(Book::class)->find($data['book_id']);

        if (!$book) {
            return $this->json([
                'error' => 'Libro no encontrado',
                'message' => "No existe un libro con ID {$data['book_id']}"
            ], Response::HTTP_BAD_REQUEST);
        }

        // Crear la reseña
        $review = new Review();
        $review->setBook($book);
        $review->setRating($data['rating']);
        $review->setComment(trim($data['comment']));

        $violations = $validator->validate($review);

        if (count($violations) > 0) {
            $errorMessages = [];
            foreach ($violations as $violation) {
                $errorMessages[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return $this->json([
                'error' => 'Errores de validación',
                'violations' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        // Guardar en la base de datos
        $entityManager->persist($review);
        $entityManager->flush();

        // Respuesta exitosa con la reseña creada
        return $this->json([
            'id' => $review->getId(),
            'book_id' => $review->getBook()->getId(),
            'rating' => $review->getRating(),
            'comment' => $review->getComment(),
            'created_at' => $review->getCreatedAt()->format('Y-m-d H:i:s')
        ], Response::HTTP_CREATED);
    }
}