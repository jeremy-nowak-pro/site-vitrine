import { Link, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

const navigation = [
    { href: '/admin/statistiques', label: 'Statistiques' },
    { href: '/admin/pieces/nouvelle', label: 'Ajouter une pièce' },
    { href: '/admin/import', label: 'Import CSV' },
];

type PropsPartagees = { auth: { nom: string; email: string } | null };

export function BandeauDemo() {
    return (
        <div role="note" className="border-b border-notice-line bg-notice text-notice-ink">
            <p className="mx-auto max-w-7xl px-4 py-2.5 text-sm font-medium sm:px-6">
                Version de démonstration : l’ajout de pièces et l’import CSV sont désactivés. Les statistiques affichent
                des données fictives.
            </p>
        </div>
    );
}

export function EtiquetteFictive() {
    return (
        <span className="inline-flex shrink-0 items-center rounded border border-notice-line bg-notice px-1.5 py-px text-[11px] font-medium whitespace-nowrap text-notice-ink">
            Données fictives
        </span>
    );
}

export default function AdminLayout({ children }: PropsWithChildren) {
    const { url, props } = usePage<PropsPartagees>();

    return (
        <div className="flex min-h-screen flex-col">
            <a
                href="#contenu"
                className="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-3 focus:z-50 focus:rounded focus:bg-canvas focus:px-3 focus:py-2 focus:text-sm"
            >
                Aller au contenu
            </a>

            <BandeauDemo />

            <header className="border-b border-line">
                <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-x-6 gap-y-2 px-4 py-3 sm:px-6">
                    <div className="flex flex-wrap items-center gap-x-6 gap-y-2">
                        <Link href="/admin" className="text-[15px] font-semibold tracking-tight">
                            Administration
                        </Link>
                        <nav aria-label="Administration">
                            <ul className="-mx-2.5 flex flex-wrap items-center gap-1 text-sm sm:mx-0">
                                {navigation.map((item) => {
                                    const actif = url.startsWith(item.href);
                                    return (
                                        <li key={item.href}>
                                            <Link
                                                href={item.href}
                                                aria-current={actif ? 'page' : undefined}
                                                className={`rounded px-2.5 py-1.5 ${actif ? 'bg-surface font-medium text-ink' : 'text-ink-muted hover:text-ink'}`}
                                            >
                                                {item.label}
                                            </Link>
                                        </li>
                                    );
                                })}
                            </ul>
                        </nav>
                    </div>
                    <div className="flex items-center gap-4 text-sm">
                        <Link href="/" className="text-ink-muted hover:text-ink">
                            Voir le site
                        </Link>
                        {props.auth && (
                            <span className="hidden text-ink-faint md:inline" title={props.auth.email}>
                                {props.auth.nom}
                            </span>
                        )}
                        <Link href="/admin/deconnexion" method="post" as="button" className="text-accent hover:underline">
                            Se déconnecter
                        </Link>
                    </div>
                </div>
            </header>

            <main id="contenu" className="mx-auto w-full min-w-0 max-w-7xl flex-1 px-4 py-8 sm:px-6">
                {children}
            </main>
        </div>
    );
}
