<?php

namespace MichaelBelgium\YoutubeAPI\Models;

use Illuminate\Support\Facades\Storage;

class Video
{
    public const URL_REGEX = '#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#';
    public const POSSIBLE_FORMATS = ['mp3', 'mp4'];

    private string $id;

    private string $title;
    private float $duration;


    private string $file;
    private ?\DateTimeInterface $uploadedAt = null;

    public function __construct(string $id, string $title, string $file)
    {
        $this->id = $id;
        $this->title = $title;
        $this->file = $file;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getUploadedAt(): ?\DateTimeInterface
    {
        return $this->uploadedAt;
    }

    public function getDuration(): float
    {
        return $this->duration;
    }

    public function setUploadedAt(?\DateTimeInterface $uploadedAt): self
    {
        $this->uploadedAt = $uploadedAt;

        return $this;
    }

    public function setDuration(float $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public static function getDownloadPath(string $file = ''): string
    {
        return Storage::disk('public')->path($file);
    }

    public static function getDownloadUrl(string $file = ''): string
    {
        return Storage::disk('public')->url($file);
    }

    public function toArray(): array
    {
        return [
            'youtube_id' => $this->getId(),
            'title' => $this->getTitle(),
            'file' => $this->getFile(),
            'uploaded_at' => $this->getUploadedAt(),
            'duration' => $this->getDuration()
        ];
    }
}