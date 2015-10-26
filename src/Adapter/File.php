<?php
namespace Norm\Adapter;

use InvalidArgumentException;
use Norm\Cursor;
use Rhumsaa\Uuid\Uuid;

class File extends Memory
{
    protected $dataDir;

    public function __construct(array $options = [])
    {

        if (!isset($options['dataDir'])) {
            throw new InvalidArgumentException(
                'File adapter does not have data directory is not available, please check your configuration.'
            );
        }

        $this->dataDir = $options['dataDir'];
    }

    public function persist($collectionName, array $row)
    {
        $id = isset($row['$id']) ? $row['$id'] : Uuid::uuid1()->__toString();

        $row = $this->marshall($row);
        $row['id'] = $id;

        $collectionDir = $this->dataDir . DIRECTORY_SEPARATOR . $collectionName . DIRECTORY_SEPARATOR;

        if (!is_dir($collectionDir)) {
            @unlink($collectionDir);
            mkdir($collectionDir, 0755, true);
        }

        file_put_contents($collectionDir . $row['id'] . '.json', json_encode($row, JSON_PRETTY_PRINT));

        return $this->unmarshall($row);
    }

    public function remove($collectionName, $rowId)
    {
        $collectionDir = $this->dataDir . DIRECTORY_SEPARATOR . $collectionName . DIRECTORY_SEPARATOR;

        @unlink($collectionDir . $rowId . '.json');
    }

    public function cursorDistinct(Cursor $cursor)
    {
        throw new \Exception('Unimplemented yet!');
    }

    public function cursorFetch(Cursor $cursor)
    {
        $context = [];


        $criteria = $this->marshall($cursor->getCriteria(), 'id');

        $query = array(
            'criteria' => $criteria,
            'limit' => $cursor->getLimit(),
            'skip' => $cursor->getSkip(),
            'sort' => $cursor->getSort(),
        );

        $collectionId = $cursor->getCollectionId();

        $collectionDir = $this->dataDir . DIRECTORY_SEPARATOR . $collectionId . DIRECTORY_SEPARATOR;

        if (!is_dir($collectionDir)) {
            @unlink($collectionDir);
            mkdir($collectionDir, 0755, true);
            return $context;
        }

        if ($dh = opendir($collectionDir)) {
            $i = 0;
            $skip = 0;

            while (($file = readdir($dh)) !== false) {
                $filename = $collectionDir . $file;
                if (is_file($filename)) {
                    $ext = pathinfo($filename, PATHINFO_EXTENSION);
                    if (strtolower($ext) === 'json') {
                        $row = file_get_contents($filename);
                        $row = json_decode($row, true);

                        if ($this->criteriaMatch($row, $query['criteria'])) {
                            if (isset($query['skip']) && $query['skip'] > $skip) {
                                $skip++;
                                continue;
                            }

                            $context[] = $row;

                            $i++;
                            if (isset($query['limit']) && $query['limit'] == $i) {
                                break;
                            }
                        }
                    }
                }
            }
            closedir($dh);
        }

        $sortValues = $query['sort'];
        if (empty($sortValues)) {
            return $context;
        }

        usort($context, function ($a, $b) use ($sortValues) {
            $context = 0;
            foreach ($sortValues as $sortKey => $sortVal) {
                $context = strcmp($a[$sortKey], $b[$sortKey]) * $sortVal * -1;
                if ($context !== 0) {
                    break;
                }
            }
            return $context;
        });
        return $context;
    }

    public function cursorSize(Cursor $cursor, $withLimitSkip = false)
    {
        return count($cursor->getContext());
    }
}
