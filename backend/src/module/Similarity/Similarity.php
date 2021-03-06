<?php

namespace App\Similarity;

use App\AbstractModule;
use App\Author\Model as Author;
use App\Helper;
use App\RenderableInterface;

class Similarity extends AbstractModule implements RenderableInterface
{
    /**
     * Author ID
     *
     * @var string
     */
    protected $authorId;

    /**
     * Similarity constructor.
     */
    public function __construct()
    {
        $this->setTitle('Similar');
    }

    /**
     * @inheritdoc
     */
    public function render($data = []): string
    {
        $output = Helper::runOnServer('similarity', $this->getAuthorId());

        $author = Author::find($this->getAuthorId());

        // Limit the amount of results
        // The first result (100%) is the author himself.
        $results = array_slice($output, 1, 5);

        // Filter out irrelevant authors (below a threshold)
        $results = array_filter($results, function ($result) {
            $threshold = 0.5;

            return ((float) $result[1]) >= $threshold;
        });

        // Generate an array of only author IDs
        $ids = array_map(function ($result) {
            return $result[0];
        }, $results);

        /** @var \PDO $connection */
        $connection = $this->getContainer()->get(\PDO::class);
        $statement = $connection->prepare('SELECT * FROM authors WHERE id = :id');

        foreach ($ids as $index => $id) {
            $statement->execute([':id' => $id]);

            $row = $statement->fetch(\PDO::FETCH_ASSOC);

            $results[$index][] = $row['name'];
        }

        return Helper::render(__DIR__ . '/view/similarity.phtml', [
            'results' => $results,
            'author'  => $author,
        ]);
    }

    /**
     * @return string
     */
    public function getAuthorId(): string
    {
        return $this->authorId;
    }

    /**
     * @param string $authorId
     */
    public function setAuthorId(string $authorId)
    {
        $this->authorId = $authorId;
    }
}
