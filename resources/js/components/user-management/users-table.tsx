import { Link, router, usePage } from '@inertiajs/react';
import { Pencil, UserCheck } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { edit, impersonate } from '@/routes/users';
import type { UserManagementData } from '@/types/generated';

export default function UsersTable({
    users,
    canImpersonate,
    onDeleteRequest,
}: {
    users: UserManagementData[];
    canImpersonate: boolean;
    onDeleteRequest: (user: UserManagementData) => void;
}) {
    const { auth } = usePage().props;

    if (!auth.user) return null;

    const currentUser = auth.user;

    function handleImpersonate(user: UserManagementData) {
        router.post(impersonate(user.id).url);
    }

    return (
        <Card className="p-0">
            <CardContent className="p-0">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Role</TableHead>
                            <TableHead>Created</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {users.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={5}
                                    className="py-8 text-center text-muted-foreground"
                                >
                                    No users found.
                                </TableCell>
                            </TableRow>
                        )}
                        {users.map((user: UserManagementData) => (
                            <TableRow key={user.id}>
                                <TableCell className="font-medium">
                                    {user.name}
                                </TableCell>
                                <TableCell>{user.email}</TableCell>
                                <TableCell>
                                    <Badge
                                        variant={
                                            user.role === 'super-admin'
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {user.role}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    {new Date(
                                        user.created_at,
                                    ).toLocaleDateString()}
                                </TableCell>
                                <TableCell className="text-right">
                                    <div className="flex items-center justify-end gap-1">
                                        {canImpersonate &&
                                            user.id !== currentUser.id && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        handleImpersonate(user)
                                                    }
                                                    title="Impersonate"
                                                >
                                                    <UserCheck className="size-4" />
                                                </Button>
                                            )}
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            asChild
                                        >
                                            <Link href={edit(user.id)} prefetch>
                                                <Pencil className="size-4" />
                                            </Link>
                                        </Button>
                                        {user.id !== currentUser.id && (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="text-destructive hover:text-destructive"
                                                onClick={() =>
                                                    onDeleteRequest(user)
                                                }
                                            >
                                                Delete
                                            </Button>
                                        )}
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    );
}
