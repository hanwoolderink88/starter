import { Head } from '@inertiajs/react';
import AuthLayout from '@/layouts/auth-layout';

export default function InvitationExpired() {
    return (
        <AuthLayout
            title="Invitation Link Expired"
            description="This invitation link has expired or is invalid."
        >
            <Head title="Invitation Expired" />
            <div className="text-center">
                <p className="mt-4 text-muted-foreground">
                    Please contact your administrator for a new invitation.
                </p>
            </div>
        </AuthLayout>
    );
}
