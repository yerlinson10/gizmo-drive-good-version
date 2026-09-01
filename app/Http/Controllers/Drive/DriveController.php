<?php

namespace App\Http\Controllers\Drive;

use App\Http\Controllers\Controller;
use App\Http\Requests\Drive\StoreFileRequest;
use App\Http\Requests\Drive\StoreFolderRequest;
use App\Http\Requests\Drive\StoreShareRequest;
use App\Models\DriveFile;
use App\Models\Folder;
use App\Models\Share;
use App\Services\Drive\DriveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DriveController extends Controller
{
    public function __construct(private DriveService $drive) {}

    public function index(Request $request): Response
    {
        return Inertia::render(
            'drive/index',
            $this->drive->browse(
                $request->user(),
                $request->integer('folder') ?: null,
            ),
        );
    }

    public function storeFolder(StoreFolderRequest $request): RedirectResponse
    {
        $folder = $this->drive->createFolder(
            $request->user(),
            $request->string('name')->toString(),
            $request->integer('parent_id') ?: null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Folder created.']);

        return to_route('drive.index', array_filter([
            'folder' => $folder->parent_id,
        ]));
    }

    public function storeFile(StoreFileRequest $request): RedirectResponse
    {
        $folderId = $request->integer('folder_id') ?: null;

        $this->drive->uploadFile(
            $request->user(),
            $request->file('file'),
            $folderId,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'File uploaded.']);

        return to_route('drive.index', array_filter([
            'folder' => $folderId,
        ]));
    }

    public function download(DriveFile $file): StreamedResponse
    {
        $this->authorize('view', $file);

        return $this->drive->download($file);
    }

    public function destroyFolder(Folder $folder): RedirectResponse
    {
        $this->authorize('delete', $folder);

        $parentId = $this->drive->deleteFolder($folder);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Folder deleted.']);

        return to_route('drive.index', array_filter([
            'folder' => $parentId,
        ]));
    }

    public function destroyFile(DriveFile $file): RedirectResponse
    {
        $this->authorize('delete', $file);

        $folderId = $this->drive->deleteFile($file);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'File deleted.']);

        return to_route('drive.index', array_filter([
            'folder' => $folderId,
        ]));
    }

    public function shareFolder(StoreShareRequest $request, Folder $folder): RedirectResponse
    {
        $this->authorize('share', $folder);

        $this->drive->share(
            $request->user(),
            $folder,
            $request->string('email')->toString(),
            $request->string('permission')->toString(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Folder shared.']);

        return back();
    }

    public function shareFile(StoreShareRequest $request, DriveFile $file): RedirectResponse
    {
        $this->authorize('share', $file);

        $this->drive->share(
            $request->user(),
            $file,
            $request->string('email')->toString(),
            $request->string('permission')->toString(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'File shared.']);

        return back();
    }

    public function revokeShare(Request $request, Share $share): RedirectResponse
    {
        $this->drive->revokeShare($request->user(), $share);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Share removed.']);

        return back();
    }
}
