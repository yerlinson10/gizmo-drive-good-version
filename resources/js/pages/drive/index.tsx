import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    Download,
    FileIcon,
    FolderIcon,
    FolderPlus,
    Share2,
    Trash2,
    Upload,
} from 'lucide-react';
import { useRef, useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard } from '@/routes';

type Owner = {
    id: number;
    name: string;
    email: string;
};

type DriveFolder = {
    id: number;
    name: string;
    parent_id: number | null;
    is_owner: boolean;
    can_edit: boolean;
    can_share: boolean;
    owner: Owner | null;
    updated_at: string | null;
};

type DriveFileItem = {
    id: number;
    name: string;
    folder_id: number | null;
    mime_type: string | null;
    size: number;
    is_owner: boolean;
    can_edit: boolean;
    can_share: boolean;
    owner: Owner | null;
    updated_at: string | null;
};

type Breadcrumb = {
    id: number;
    name: string;
};

type Props = {
    currentFolder: DriveFolder | null;
    breadcrumbs: Breadcrumb[];
    folders: DriveFolder[];
    files: DriveFileItem[];
    sharedWithMe: {
        folders: DriveFolder[];
        files: DriveFileItem[];
    } | null;
    canCreate: boolean;
};

function formatBytes(bytes: number): string {
    if (bytes === 0) {
        return '0 B';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    const index = Math.min(
        Math.floor(Math.log(bytes) / Math.log(1024)),
        units.length - 1,
    );
    const value = bytes / 1024 ** index;

    return `${value.toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
}

export default function DriveIndex({
    currentFolder,
    breadcrumbs,
    folders,
    files,
    sharedWithMe,
    canCreate,
}: Props) {
    const [folderOpen, setFolderOpen] = useState(false);
    const [shareOpen, setShareOpen] = useState(false);
    const [shareTarget, setShareTarget] = useState<{
        type: 'folder' | 'file';
        id: number;
        name: string;
    } | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const folderForm = useForm({
        name: '',
        parent_id: currentFolder?.id ?? null as number | null,
    });

    const fileForm = useForm<{
        file: File | null;
        folder_id: number | null;
    }>({
        file: null,
        folder_id: currentFolder?.id ?? null,
    });

    const shareForm = useForm({
        email: '',
        permission: 'view',
    });

    function openShare(type: 'folder' | 'file', id: number, name: string) {
        setShareTarget({ type, id, name });
        shareForm.reset();
        shareForm.setData('permission', 'view');
        setShareOpen(true);
    }

    function submitFolder(event: React.FormEvent) {
        event.preventDefault();
        folderForm.transform((data) => ({
            ...data,
            parent_id: currentFolder?.id ?? null,
        }));
        folderForm.post('/drive/folders', {
            preserveScroll: true,
            onSuccess: () => {
                folderForm.reset('name');
                setFolderOpen(false);
            },
        });
    }

    function submitUpload(event: React.ChangeEvent<HTMLInputElement>) {
        const selected = event.target.files?.[0];

        if (!selected) {
            return;
        }

        fileForm.setData({
            file: selected,
            folder_id: currentFolder?.id ?? null,
        });

        fileForm.post('/drive/files', {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => {
                fileForm.reset('file');
                if (fileInputRef.current) {
                    fileInputRef.current.value = '';
                }
            },
        });
    }

    function submitShare(event: React.FormEvent) {
        event.preventDefault();

        if (!shareTarget) {
            return;
        }

        const url =
            shareTarget.type === 'folder'
                ? `/drive/folders/${shareTarget.id}/share`
                : `/drive/files/${shareTarget.id}/share`;

        shareForm.post(url, {
            preserveScroll: true,
            onSuccess: () => {
                setShareOpen(false);
                shareForm.reset();
            },
        });
    }

    return (
        <>
            <Head title="Drive" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="space-y-2">
                        <Heading
                            title={currentFolder?.name ?? 'My Drive'}
                            description="Create folders, upload files, and share them with other users."
                        />
                        <nav className="text-muted-foreground flex flex-wrap items-center gap-1 text-sm">
                            <Link
                                href="/drive"
                                className="hover:text-foreground transition-colors"
                            >
                                Drive
                            </Link>
                            {breadcrumbs.map((crumb) => (
                                <span
                                    key={crumb.id}
                                    className="flex items-center gap-1"
                                >
                                    <span>/</span>
                                    <Link
                                        href={`/drive?folder=${crumb.id}`}
                                        className="hover:text-foreground transition-colors"
                                    >
                                        {crumb.name}
                                    </Link>
                                </span>
                            ))}
                        </nav>
                    </div>

                    {canCreate && (
                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setFolderOpen(true)}
                            >
                                <FolderPlus className="size-4" />
                                New folder
                            </Button>
                            <Button
                                type="button"
                                onClick={() => fileInputRef.current?.click()}
                                disabled={fileForm.processing}
                            >
                                <Upload className="size-4" />
                                Upload file
                            </Button>
                            <input
                                ref={fileInputRef}
                                type="file"
                                className="hidden"
                                onChange={submitUpload}
                            />
                        </div>
                    )}
                </div>

                <InputError message={fileForm.errors.file} />
                <InputError message={fileForm.errors.folder_id} />

                <section className="space-y-3">
                    <h2 className="text-sm font-medium tracking-wide uppercase">
                        Folders
                    </h2>
                    {folders.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            No folders here yet.
                        </p>
                    ) : (
                        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            {folders.map((folder) => (
                                <div
                                    key={folder.id}
                                    className="border-sidebar-border/80 bg-background flex items-center justify-between gap-3 rounded-xl border px-4 py-3"
                                >
                                    <Link
                                        href={`/drive?folder=${folder.id}`}
                                        className="flex min-w-0 flex-1 items-center gap-3"
                                    >
                                        <FolderIcon className="text-amber-600 size-5 shrink-0 dark:text-amber-400" />
                                        <span className="truncate font-medium">
                                            {folder.name}
                                        </span>
                                    </Link>
                                    <div className="flex shrink-0 items-center gap-1">
                                        {folder.can_share && (
                                            <Button
                                                type="button"
                                                size="icon"
                                                variant="ghost"
                                                onClick={() =>
                                                    openShare(
                                                        'folder',
                                                        folder.id,
                                                        folder.name,
                                                    )
                                                }
                                            >
                                                <Share2 className="size-4" />
                                            </Button>
                                        )}
                                        {folder.is_owner && (
                                            <Button
                                                type="button"
                                                size="icon"
                                                variant="ghost"
                                                onClick={() =>
                                                    router.delete(
                                                        `/drive/folders/${folder.id}`,
                                                    )
                                                }
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </section>

                <section className="space-y-3">
                    <h2 className="text-sm font-medium tracking-wide uppercase">
                        Files
                    </h2>
                    {files.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            No files here yet.
                        </p>
                    ) : (
                        <div className="overflow-hidden rounded-xl border">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/40 text-muted-foreground text-left">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Name
                                        </th>
                                        <th className="hidden px-4 py-3 font-medium md:table-cell">
                                            Size
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {files.map((file) => (
                                        <tr
                                            key={file.id}
                                            className="border-t"
                                        >
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-3">
                                                    <FileIcon className="text-muted-foreground size-4 shrink-0" />
                                                    <span className="font-medium">
                                                        {file.name}
                                                    </span>
                                                </div>
                                            </td>
                                            <td className="text-muted-foreground hidden px-4 py-3 md:table-cell">
                                                {formatBytes(file.size)}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-1">
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="ghost"
                                                        asChild
                                                    >
                                                        <a
                                                            href={`/drive/files/${file.id}/download`}
                                                        >
                                                            <Download className="size-4" />
                                                        </a>
                                                    </Button>
                                                    {file.can_share && (
                                                        <Button
                                                            type="button"
                                                            size="icon"
                                                            variant="ghost"
                                                            onClick={() =>
                                                                openShare(
                                                                    'file',
                                                                    file.id,
                                                                    file.name,
                                                                )
                                                            }
                                                        >
                                                            <Share2 className="size-4" />
                                                        </Button>
                                                    )}
                                                    {file.is_owner && (
                                                        <Button
                                                            type="button"
                                                            size="icon"
                                                            variant="ghost"
                                                            onClick={() =>
                                                                router.delete(
                                                                    `/drive/files/${file.id}`,
                                                                )
                                                            }
                                                        >
                                                            <Trash2 className="size-4" />
                                                        </Button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </section>

                {sharedWithMe &&
                    (sharedWithMe.folders.length > 0 ||
                        sharedWithMe.files.length > 0) && (
                        <section className="space-y-3">
                            <h2 className="text-sm font-medium tracking-wide uppercase">
                                Shared with me
                            </h2>
                            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                {sharedWithMe.folders.map((folder) => (
                                    <Link
                                        key={`shared-folder-${folder.id}`}
                                        href={`/drive?folder=${folder.id}`}
                                        className="border-sidebar-border/80 bg-background flex items-center gap-3 rounded-xl border px-4 py-3"
                                    >
                                        <FolderIcon className="text-amber-600 size-5 shrink-0 dark:text-amber-400" />
                                        <div className="min-w-0">
                                            <div className="truncate font-medium">
                                                {folder.name}
                                            </div>
                                            <div className="text-muted-foreground truncate text-xs">
                                                From {folder.owner?.name}
                                            </div>
                                        </div>
                                    </Link>
                                ))}
                                {sharedWithMe.files.map((file) => (
                                    <a
                                        key={`shared-file-${file.id}`}
                                        href={`/drive/files/${file.id}/download`}
                                        className="border-sidebar-border/80 bg-background flex items-center gap-3 rounded-xl border px-4 py-3"
                                    >
                                        <FileIcon className="text-muted-foreground size-5 shrink-0" />
                                        <div className="min-w-0">
                                            <div className="truncate font-medium">
                                                {file.name}
                                            </div>
                                            <div className="text-muted-foreground truncate text-xs">
                                                From {file.owner?.name}
                                            </div>
                                        </div>
                                    </a>
                                ))}
                            </div>
                        </section>
                    )}
            </div>

            <Dialog open={folderOpen} onOpenChange={setFolderOpen}>
                <DialogContent>
                    <form onSubmit={submitFolder} className="space-y-4">
                        <DialogHeader>
                            <DialogTitle>Create folder</DialogTitle>
                            <DialogDescription>
                                Add a new folder
                                {currentFolder
                                    ? ` inside ${currentFolder.name}`
                                    : ' in your drive root'}
                                .
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-2">
                            <Label htmlFor="folder-name">Name</Label>
                            <Input
                                id="folder-name"
                                value={folderForm.data.name}
                                onChange={(event) =>
                                    folderForm.setData(
                                        'name',
                                        event.target.value,
                                    )
                                }
                                autoFocus
                            />
                            <InputError message={folderForm.errors.name} />
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setFolderOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={folderForm.processing}
                            >
                                Create
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={shareOpen} onOpenChange={setShareOpen}>
                <DialogContent>
                    <form onSubmit={submitShare} className="space-y-4">
                        <DialogHeader>
                            <DialogTitle>Share {shareTarget?.name}</DialogTitle>
                            <DialogDescription>
                                Share with another registered user by email.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-2">
                            <Label htmlFor="share-email">Email</Label>
                            <Input
                                id="share-email"
                                type="email"
                                value={shareForm.data.email}
                                onChange={(event) =>
                                    shareForm.setData(
                                        'email',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={shareForm.errors.email} />
                        </div>
                        <div className="space-y-2">
                            <Label>Permission</Label>
                            <Select
                                value={shareForm.data.permission}
                                onValueChange={(value) =>
                                    shareForm.setData('permission', value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Permission" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="view">View</SelectItem>
                                    <SelectItem value="edit">Edit</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError
                                message={shareForm.errors.permission}
                            />
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setShareOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={shareForm.processing}
                            >
                                Share
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

DriveIndex.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Drive',
            href: '/drive',
        },
    ],
};
