import { lazy, Suspense } from 'react';
import type { ExportStl } from '@/components/visionneuse/Visionneuse';
import { formaterMm } from '@/lib/catalogue';
import type { FichePiece } from '@/types/piece';

// Three.js, React Three Fiber et drei ne sont chargés que sur la fiche pièce, dans un fichier séparé.
const Visionneuse = lazy(() => import('@/components/visionneuse/Visionneuse'));

type Props = {
    piece: FichePiece;
    onExportPret: (exporter: ExportStl | null) => void;
};

export default function ZoneVisionneuse({ piece, onExportPret }: Props) {
    return (
        <Suspense fallback={<Attente piece={piece} />}>
            <Visionneuse piece={piece} onExportPret={onExportPret} />
        </Suspense>
    );
}

function Attente({ piece }: { piece: FichePiece }) {
    return (
        <div
            className="flex aspect-[4/3] flex-col items-center justify-center gap-2 rounded-md border border-line bg-surface"
            style={{
                backgroundImage:
                    'linear-gradient(var(--color-line) 1px, transparent 1px), linear-gradient(90deg, var(--color-line) 1px, transparent 1px)',
                backgroundSize: '24px 24px',
            }}
        >
            <p className="rounded bg-canvas px-3 py-1.5 text-sm text-ink-muted">Chargement de la visionneuse 3D…</p>
            <p className="rounded bg-canvas px-2 py-1 font-mono text-xs text-ink-faint">
                {formaterMm(piece.dimensions.longueur)} × {formaterMm(piece.dimensions.largeur)} × {formaterMm(piece.dimensions.hauteur)}
            </p>
        </div>
    );
}
