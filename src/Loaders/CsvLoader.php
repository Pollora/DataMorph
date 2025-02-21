<?php
namespace Pollora\Datamorph\Loaders;

use League\Csv\Writer;
use Pollora\Datamorph\Loaders\LoaderInterface;
use Illuminate\Support\Facades\Storage;
use SplFileObject;

class CsvLoader implements LoaderInterface
{
    protected string $path;
    protected string $delimiter;
    protected string $enclosure;
    protected bool $includeHeaders;

    /**
     * Configure le CsvLoader avec les options fournies.
     *
     * @param array $options
     * @return self
     */
    public function configure(array $options): self
    {
        $this->path = $options['path'] ?? storage_path('app/exports/default.csv');
        $this->delimiter = $options['delimiter'] ?? ';';
        $this->enclosure = $options['enclosure'] ?? '"';
        $this->includeHeaders = $options['include_headers'] ?? true;

        return $this;
    }

    /**
     * Charge les données dans un fichier CSV.
     *
     * @param iterable $data
     */
    public function load(iterable $data): void
    {
        // Création du fichier et initialisation de League CSV
        $writer = Writer::createFromPath($this->path, 'w+');
        $writer->setDelimiter($this->delimiter);
        $writer->setEnclosure($this->enclosure);
        $writer->setOutputBOM(Writer::BOM_UTF8);

        // Ajout des en-têtes si nécessaire
        if ($this->includeHeaders && !empty($data)) {
            $headers = array_keys($data[0]);
            $writer->insertOne($headers);
        }

        // Écriture des données ligne par ligne en streaming
        foreach ($data as $row) {
            $writer->insertOne(array_values($row));
        }
    }
}
