import { useFavoris } from '@/hooks/useFavoris';

export default function BoutonFavori({ reference }: { reference: string }) {
    const { estFavori, basculer } = useFavoris();
    const actif = estFavori(reference);

    return (
        <button
            type="button"
            onClick={() => basculer(reference)}
            aria-pressed={actif}
            className={`inline-flex h-9 items-center gap-2 rounded border px-3.5 text-sm font-medium ${
                actif ? 'border-accent bg-accent-soft text-accent-strong' : 'border-line-strong hover:bg-surface'
            }`}
        >
            <svg viewBox="0 0 20 20" aria-hidden="true" className="size-4" fill={actif ? 'currentColor' : 'none'} stroke="currentColor" strokeWidth="1.6">
                <path d="M10 2.6l2.2 4.6 5 .7-3.6 3.5.9 5-4.5-2.4-4.5 2.4.9-5L2.8 7.9l5-.7z" strokeLinejoin="round" />
            </svg>
            {actif ? 'Dans vos favoris' : 'Ajouter aux favoris'}
        </button>
    );
}
