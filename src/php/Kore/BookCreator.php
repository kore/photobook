<?php

namespace Kore;

use Symfony\Component\Yaml\Yaml;

class BookCreator
{
    protected $dateMatchingRegularExpressions = [
    ];

    public function fromDirectory(string $directory): string
    {
        $directory = realpath($directory);
        if (!$directory) {
            throw new \OutOfBoundsException('Directory not found');
        }

        $configuration = [
            'book' => [
                'title' => ucwords(preg_replace('([^A-Za-z0-9]+)', ' ', basename($directory))),
                'baseDir' => $directory,
                'format' => [
                    'width' => 303,
                    'height' => 216,
                    'cutOff' => 3,
                ],
                'production' => false,
            ],
            'pages' => [],
        ];

        // Find all photos and index them by the time they were taken
        $photos = [];
        foreach (glob($directory.'/*') as $potentialImageFile) {
            if (0 !== strpos(mime_content_type($potentialImageFile), 'image/')) {
                continue;
            }

            $exifData = exif_read_data($potentialImageFile);
            $dateTime = $exifData['DateTime'] ?? $exifData['DateTimeOriginal'] ?? $exifData['DateTimeDigitized'] ?? null;
            $dateTime = $dateTime ? new \DateTimeImmutable($dateTime) : null;

            if (!$dateTime && preg_match('((\\d+)[_-](\\d+)[_-](\\d+)(?:[_-](\\d{1,2})[_-](\\d{1,2})[_-](\\d{1,2}))?)', basename($potentialImageFile), $match)) {
                $dateTime = new \DateTimeImmutable(sprintf('%d.%d.%d %d:%d:%d', $match[3], $match[2], $match[1], $match[4] ?? 12, $match[5] ?? 0, $match[6] ?? 0));
            }

            if (!$dateTime) {
                throw new \RuntimeException("Could not determine date for $potentialImageFile");
            }

            $photos[$dateTime->getTimeStamp()] = $potentialImageFile;
        }

        ksort($photos);

        // Find groups of photos based on the time distance between them
        $startDate = min(array_keys($photos));
        $endDate = max(array_keys($photos));
        $averageDistance = ($endDate - $startDate) / count($photos);

        $groups = [];
        $group = 0;
        $lastDateTime = null;
        foreach ($photos as $dateTime => $photo) {
            if ($lastDateTime && ($dateTime - $lastDateTime) > $averageDistance) {
                ++$group;
            }

            $groups[$group][$dateTime] = basename($photo);
            $lastDateTime = $dateTime;
        }

        // Reduce group sizes to (at most) 5 photos in a single group
        $splittedGroups = [];
        foreach ($groups as $group) {
            if (count($group) <= 5) {
                $splittedGroups[] = $group;
                continue;
            }

            // Always pick the first image as potential caption slide
            $splittedGroups[] = [array_shift($group)];

            while (count($group) > 5) {
                $takeImages = mt_rand(1, 5);
                $splittedGroups[] = array_slice($group, 0, $takeImages);
                $group = array_slice($group, $takeImages);
            }

            $splittedGroups[] = $group;
        }

        // Create pages based on the amount of photos in a group
        $lastDateTime = null;
        foreach ($splittedGroups as $group) {
            $groupStartDate = min(array_keys($group));
            $groupEndDate = max(array_keys($group));
            switch (count($group)) {
                case 1:
                    if (!$lastDateTime || ($groupStartDate - $lastDateTime) > ($averageDistance * 5)) {
                        $configuration['pages'][] = [
                            'type' => 'cleardoublepage',
                        ];
                        $configuration['pages'][] = [
                            'type' => 'caption',
                            'caption' => 'Caption',
                            'photo' => reset($group),
                            'position' => 0.2,
                        ];
                    } else {
                        $configuration['pages'][] = reset($group);
                    }
                    break;
                case 2:
                    $configuration['pages'][] = [
                        'type' => 'panel',
                        'orientation' => 'horizontal',
                        'border' => 2,
                        'borderColor' => '#ffffff',
                        'photos' => array_values($group),
                    ];
                    break;
                case 3:
                    $configuration['pages'][] = [
                        'type' => 'grid',
                        'border' => 2,
                        'borderColor' => '#ffffff',
                        'rows' => [1, 2],
                        'photos' => array_values($group),
                    ];
                    break;
                case 4:
                    $configuration['pages'][] = [
                        'type' => 'spread',
                        'photos' => array_values($group),
                    ];
                    break;
                case 5:
                    $configuration['pages'][] = [
                        'type' => 'grid',
                        'border' => 2,
                        'borderColor' => '#ffffff',
                        'rows' => [3, 2],
                        'photos' => array_values($group),
                    ];
                    break;
                default:
                    throw new \OutOfBoundsException('No page type available for group.');
            }

            $lastDateTime = $groupEndDate;
        }

        return Yaml::dump($configuration, 5, 2);
    }
}
