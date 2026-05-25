<?php

namespace App\Services;

use App\Interfaces\AttachmentServiceInterface;
use App\Models\Attachment;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Override;

class AttachmentService implements AttachmentServiceInterface
{
    protected string $defaultDisk;

    public function __construct()
    {
        $this->defaultDisk = config('filesystems.default', 'public');
    }

    #[Override]
    public function uploadAndAttach(UploadedFile $file, Model $attachable, string $folder = 'attachments'): Attachment
    {
        $securName = Str::random(40).'.'.$file->getClientOriginalExtension();

        $subFolder = $folder.'/'.Str::plural(class_basename($attachable));
        $path = $file->storeAs($subFolder, $securName, $this->defaultDisk);

        if (! $path) {
            throw new Exception('uploading file failed');
        }

        return $attachable->attachments()->create([
            'path' => $path,
            'disk' => $this->defaultDisk,
            'original_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientMimeType(),
        ]);
    }

    #[Override]
    public function uploadMultipleAndAttach(array $files, Model $attachable, string $folder = 'attachments'): Collection
    {
        $uploadedAttachments = collect();
        foreach ($files as $file) {
            $uploadedAttachments->push($this->uploadAndAttach($file, $attachable, $folder));
        }

        return $uploadedAttachments;
    }

    #[Override]
    public function delete(Attachment $attachment): bool
    {
        $this->deletePhysicalFile($attachment->path, $attachment->disk);

        return $attachment->delete();
    }

    #[Override]
    public function deletePhysicalFile(string $path, string $disk): bool
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }
}
