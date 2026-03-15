<?php

namespace App\Controller;

use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class BookController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    
    // Versión v1 Listar libros 
    // #[Route('/books', name: 'app_book', methods:['GET'])]
    // public function index(): JsonResponse
    // {
    //     $datos = $this->em->getRepository(Book::class)->findBY(array(), array('id'=>'desc'));
    //     return $this->json($datos);
    // }

    /**
     * GET /api/books - Listar libros 
     */
    #[Route('/books', name: 'api_books_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $qb = $this->em->createQueryBuilder();
        
        $qb->select('b.id', 'b.title', 'b.author', 'b.published_year', 'COALESCE(AVG(r.rating), 0) as average_rating', 'COUNT(r.id) as review_count')
           ->from(Book::class, 'b')
           ->leftJoin('b.reviews', 'r')
           ->groupBy('b.id')
           ->orderBy('b.id', 'DESC');

        $results = $qb->getQuery()->getResult();

        $books = array_map(function($book) {
            return [
                'id' => $book['id'],
                'title' => $book['title'],
                'author' => $book['author'],
                'published_year' => $book['published_year'],
                'average_rating' => $book['average_rating'] > 0 
                    ? round((float)$book['average_rating'], 1) 
                    : null,
                'review_count' => (int)$book['review_count']
            ];
        }, $results);

        return $this->json($books);
    }

    /**
     * GET /api/books/{id} - Obtener un libro por ID 
     */
    #[Route('/books/{id}', name: 'api_book_detail', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $book = $this->em->getRepository(Book::class)->find($id);

        if (!$book) {
            return $this->json([
                'error' => 'Libro no encontrado',
                'message' => "No existe un libro con ID {$id}"
            ], Response::HTTP_NOT_FOUND);
        }

        $reviews = [];
        foreach ($book->getReviews() as $review) {
            $reviews[] = [
                'id' => $review->getId(),
                'rating' => $review->getRating(),
                'comment' => $review->getComment(),
                'created_at' => $review->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }

        $totalRating = 0;
        $reviewCount = count($reviews);
        
        foreach ($reviews as $review) {
            $totalRating += $review['rating'];
        }
        
        $averageRating = $reviewCount > 0 ? round($totalRating / $reviewCount, 1) : null;

        return $this->json([
            'id' => $book->getId(),
            'title' => $book->getTitle(),
            'author' => $book->getAuthor(),
            'published_year' => $book->getPublishedYear(),
            'average_rating' => $averageRating,
            'review_count' => $reviewCount,
            'reviews' => $reviews
        ]);
    }

    /**
     * POST /api/books - Crear un nuevo libro
     */
    #[Route('/books', name: 'api_book_create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json([
                'error' => 'JSON inválido',
                'message' => json_last_error_msg()
            ], Response::HTTP_BAD_REQUEST);
        }

        // Valido los camppos
        $errors = [];

        if (!isset($data['title']) || trim($data['title']) === '') {
            $errors['title'] = 'El título es obligatorio';
        }

        if (!isset($data['author']) || trim($data['author']) === '') {
            $errors['author'] = 'El autor es obligatorio';
        }

        if (!isset($data['published_year'])) {
            $errors['published_year'] = 'El año de publicación es obligatorio';
        } elseif (!is_int($data['published_year']) || $data['published_year'] < 1000 || $data['published_year'] > 2100) {
            $errors['published_year'] = 'El año debe ser un número entre 1000 y 2100';
        }

        if (!empty($errors)) {
            return $this->json([
                'error' => 'Errores de validación',
                'violations' => $errors
            ], Response::HTTP_BAD_REQUEST);
        }

        // Crear el libro
        $book = new Book();
        $book->setTitle(trim($data['title']));
        $book->setAuthor(trim($data['author']));
        $book->setPublishedYear($data['published_year']);

        $violations = $validator->validate($book);

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

        $this->em->persist($book);
        $this->em->flush();

        return $this->json([
            'id' => $book->getId(),
            'title' => $book->getTitle(),
            'author' => $book->getAuthor(),
            'published_year' => $book->getPublishedYear(),
            'message' => 'Libro creado exitosamente'
        ], Response::HTTP_CREATED);
    }

    /**
     * PUT /api/books/{id} - Actualizar 
     */
    #[Route('/books/{id}', name: 'api_book_update', methods: ['PUT'])]
    public function update(int $id, Request $request, ValidatorInterface $validator): JsonResponse
    {
        $book = $this->em->getRepository(Book::class)->find($id);

        if (!$book) {
            return $this->json([
                'error' => 'Libro no encontrado',
                'message' => "No existe un libro con ID {$id}"
            ], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json([
                'error' => 'JSON inválido',
                'message' => json_last_error_msg()
            ], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['title'])) {
            $book->setTitle(trim($data['title']));
        }

        if (isset($data['author'])) {
            $book->setAuthor(trim($data['author']));
        }

        if (isset($data['published_year'])) {
            $book->setPublishedYear($data['published_year']);
        }

        $violations = $validator->validate($book);

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

        $this->em->flush();

        return $this->json([
            'id' => $book->getId(),
            'title' => $book->getTitle(),
            'author' => $book->getAuthor(),
            'published_year' => $book->getPublishedYear(),
            'message' => 'Libro actualizado exitosamente'
        ]);
    }

    /**
     * DELETE /api/books/{id} - Eliminar 
     */
    #[Route('/books/{id}', name: 'api_book_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $book = $this->em->getRepository(Book::class)->find($id);

        if (!$book) {
            return $this->json([
                'error' => 'Libro no encontrado',
                'message' => "No existe un libro con ID {$id}"
            ], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($book);
        $this->em->flush();

        return $this->json([
            'message' => 'Libro eliminado exitosamente'
        ], Response::HTTP_OK);
    }
}