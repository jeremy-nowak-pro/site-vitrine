import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/layouts/PublicLayout';

const MESSAGES: Record<number, { titre: string; texte: string }> = {
    403: { titre: 'Accès refusé', texte: 'Vous n’avez pas les droits nécessaires pour afficher cette page.' },
    404: { titre: 'Page introuvable', texte: 'Cette page n’existe pas ou plus. La pièce a peut-être été retirée du catalogue.' },
    429: { titre: 'Trop de requêtes', texte: 'Patientez quelques instants avant de réessayer.' },
    500: { titre: 'Une erreur est survenue', texte: 'L’incident a été enregistré. Réessayez dans quelques instants.' },
    503: { titre: 'Service en maintenance', texte: 'Le catalogue revient très vite.' },
};

export default function Erreur({ statut }: { statut: number }) {
    const message = MESSAGES[statut] ?? MESSAGES[500];

    return (
        <PublicLayout>
            <Head title={message.titre} />
            <div className="max-w-xl py-10">
                <p className="font-mono text-sm text-ink-faint">Erreur {statut}</p>
                <h1 className="mt-1 text-2xl font-semibold tracking-tight">{message.titre}</h1>
                <p className="mt-2 text-ink-muted">{message.texte}</p>
                <Link href="/" className="mt-6 inline-flex h-9 items-center rounded border border-line-strong px-3.5 text-sm font-medium hover:bg-surface">
                    Retour au catalogue
                </Link>
            </div>
        </PublicLayout>
    );
}
