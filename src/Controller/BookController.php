<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Serializable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BookController extends AbstractController
{
    #[Route('/api/books', name: 'books', methods: ['GET'])]
    public function getAllBooks(BookRepository $bookRepository, SerializerInterface $serializer): JsonResponse
    {
        $bookList = $bookRepository->findAll();

        $jsonBookList = $serializer->serialize($bookList, 'json', ['groups' => 'getBooks']);
        return new JsonResponse($jsonBookList, Response::HTTP_OK, [], true);
    }
    //-----------------------READ - GET-------------------------------------------
    #[Route('/api/books/{id}', name: 'detailBook', methods: ['GET'])]
    public function getDetailBook($id, BookRepository $bookRepository, SerializerInterface $serializer): JsonResponse
    {
        $book = $bookRepository->find($id);
        if ($book) {
            $jsonBookList = $serializer->serialize($book, 'json',  ['groups' => 'getBooks']);
            return new JsonResponse($jsonBookList, Response::HTTP_OK, ["nom" => "KODJO"], true);
        }
        return new JsonResponse([
            "message" => "Livre non trouvé",
        ], Response::HTTP_NOT_FOUND);
        // $jsonBookList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/books/{id}/description', name: 'descriptionBook', methods: ['GET'])]
    public function getDescriptionBook(Book $book, BookRepository $bookRepository, SerializerInterface $serializer): JsonResponse
    {
        // $book = $bookRepository->find($id);
        if ($book) {
            $jsonBookDescription = $serializer->serialize($book->getCoverText(), 'json',  ['groups' => 'getDescription']);
            return new JsonResponse($jsonBookDescription, Response::HTTP_OK, [], true);
        }
        return new JsonResponse([
            "Non disponible",
            Response::HTTP_NOT_FOUND,
            [],
            false
        ]);
        // $jsonBookList, Response::HTTP_OK, [], true);
    }
    //-----------------------DELETE-------------------------------------------
    #[Route('/api/books/{id}', name: 'deleteBook', methods: ['DELETE'])]
    public function deleteBook(Book $book, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($book);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }


    // -----------------------CREATE-------------------------------------------
    #[Route('/api/books', name: "createBook", methods: ['POST'])]
    public function createBook(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        AuthorRepository $authorRepository,
        ValidatorInterface $validator
    ): JsonResponse {
        // Désérialisation des données JSON en un objet Book
        $book = $serializer->deserialize($request->getContent(), Book::class, 'json');

        // Récupération des données de la requête sous forme de tableau
        $requestData = $request->toArray();

        // Récupération de l'id de l'auteur. Si non défini, utilisez -1 par défaut.
        $authorId = $requestData['idAuthor'] ?? -1;

        // Recherche de l'auteur correspondant ou null s'il n'existe pas
        $author = $authorRepository->find($authorId);

        if ($author) {
            $book->setAuthor($author);
        }

        // Validation de l'objet Book
        // On vérifie les erreurs
        $errors = $validator->validate($book);

        if ($errors->count() > 0) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, "La requête est invalide");
            // return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }


        // Persistez le livre et effectuez la sauvegarde
        $entityManager->persist($book);
        $entityManager->flush();


        // Sérialisation de l'objet Book en JSON
        $jsonBook = $serializer->serialize($book, 'json', ['groups' => 'getBooks']);
        $location = $urlGenerator->generate('detailBook', ['id' => $book->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        // Réponse JSON avec le livre créé, un statut HTTP 201 Created et un en-tête "Location" pointant vers l'URL de détails du livre
        return new JsonResponse($jsonBook, Response::HTTP_CREATED, ["Location" => $location, "test " => "Kodjo"], true);
    }


    // -----------------------UPDATE-------------------------------------------
    #[Route('/api/books/{id}', name: "updateBook", methods: ['PUT'])]
    public function updateBook(Request $request, SerializerInterface $serializer, Book $currentBook, EntityManagerInterface $em, AuthorRepository $authorRepository): JsonResponse
    {
        $updatedBook = $serializer->deserialize(
            $request->getContent(),
            Book::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $currentBook]
        );
        $content = $request->toArray();
        $idAuthor = $content['idAuthor'] ?? -1;
        $updatedBook->setAuthor($authorRepository->find($idAuthor));

        $em->persist($updatedBook);
        $em->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }


    // #[Route('/api/books/{id}', name: "updateBook", methods: ['PUT'])]
    // public function updateBook($id, Request $request, SerializerInterface $serializer, EntityManagerInterface $em, AuthorRepository $authorRepository): JsonResponse
    // {
    //     $currentBook = $authorRepository->find($id);
    //     $updatedBook = $serializer->deserialize(
    //         $request->getContent(),
    //         Book::class,
    //         'json',
    //         [AbstractNormalizer::OBJECT_TO_POPULATE => $currentBook]
    //     );

    //     $content = $request->toArray();
    //     $idAuthor = $content['idAuthor'] ?? -1;
    //     $updatedBook->setAuthor($authorRepository->find($idAuthor));

    //     $em->persist($updatedBook);
    //     $em->flush();
    //     //
    //     $jsonBookList = $serializer->serialize($updatedBook, 'json',  ['groups' => 'getBooks']);

    //     //
    //     return new JsonResponse($jsonBookList, JsonResponse::HTTP_OK, [], true);
    // }
}
