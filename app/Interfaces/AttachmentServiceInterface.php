<?php

namespace App\Interfaces;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

interface AttachmentServiceInterface
{
    // upload one file and attach it with the object
    public function uploadAndAttach(UploadedFile $file, Model $attachable, string $folder = 'attachments'): Attachment;

    // upload multiple files
    public function uploadMultipleAndAttach(array $files, Model $attachable, string $folder = 'attachments'): Collection;

    public function delete(Attachment $attachment): bool;

    public function deletePhysicalFile(string $path, string $disk): bool;
}
