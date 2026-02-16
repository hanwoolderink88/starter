import { Head } from '@inertiajs/react';
import HeroSection from '@/components/welcome/hero-section';
import Navigation from '@/components/welcome/navigation';
import type { PageProps } from '@/types';
import type { WelcomePageData } from '@/types/generated';

export default function Welcome({
    auth,
    canRegister,
}: PageProps<WelcomePageData>) {
    return (
        <>
            <Head title="Welcome">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600"
                    rel="stylesheet"
                />
            </Head>
            <div className="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]">
                <Navigation auth={auth} canRegister={canRegister} />
                <HeroSection />
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </>
    );
}
