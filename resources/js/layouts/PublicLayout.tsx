import { Link, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

const navigation = [
    { href: '/', label: 'Catalogue', match: (url: string) => url === '/' || url.startsWith('/pieces') },
    { href: '/documentation', label: 'Documentation', match: (url: string) => url.startsWith('/documentation') },
    { href: '/favoris', label: 'Favoris', match: (url: string) => url.startsWith('/favoris') },
];

export default function PublicLayout({ children }: PropsWithChildren) {
    const { url } = usePage();

    return (
        <div className="flex min-h-screen flex-col">
            <a
                href="#contenu"
                className="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-3 focus:z-50 focus:rounded focus:bg-canvas focus:px-3 focus:py-2 focus:text-sm"
            >
                Aller au contenu
            </a>

            <header className="border-b border-line">
                <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-x-6 gap-y-1 px-4 py-2.5 sm:h-14 sm:py-0 sm:px-6">
                    <Link href="/" className="text-[15px] font-semibold tracking-tight">
                        Catalogue Miniatures
                    </Link>
                    <nav aria-label="Navigation principale">
                        <ul className="-mx-2.5 flex items-center gap-1 text-sm sm:mx-0">
                            {navigation.map((item) => {
                                const active = item.match(url);
                                return (
                                    <li key={item.href}>
                                        <Link
                                            href={item.href}
                                            aria-current={active ? 'page' : undefined}
                                            className={`rounded px-2.5 py-1.5 ${
                                                active ? 'text-ink font-medium bg-surface' : 'text-ink-muted hover:text-ink'
                                            }`}
                                        >
                                            {item.label}
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>
                    </nav>
                </div>
            </header>

            <main id="contenu" className="mx-auto w-full min-w-0 max-w-7xl flex-1 px-4 py-8 sm:px-6 sm:py-10">
                {children}
            </main>

            <footer className="border-t border-line">
                <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-5 text-xs text-ink-faint sm:px-6">
                    <span>Catalogue de démonstration, données fictives.</span>
                    <Link href="/admin" className="hover:text-ink">
                        Administration
                    </Link>
                </div>
            </footer>
        </div>
    );
}
