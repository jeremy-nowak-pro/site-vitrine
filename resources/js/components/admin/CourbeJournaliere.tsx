import { useEffect, useId, useRef, useState } from 'react';
import { formaterNombre } from '@/lib/catalogue';

type Point = { date: string; valeur: number };

type Props = {
    titre: string;
    points: Point[];
    /** Libellé de la valeur dans l'infobulle et le tableau, ex. « consultations ». */
    unite: string;
};

const HAUTEUR = 180;
const MARGE = { haut: 12, droite: 12, bas: 26, gauche: 44 };

const jourCourt = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'short' });
const jourLong = new Intl.DateTimeFormat('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' });

const dateDe = (iso: string) => new Date(`${iso}T00:00:00`);

/**
 * Graduations « rondes » de 0 au maximum, 3 ou 4 intervalles.
 */
function graduations(maximum: number): number[] {
    const brut = maximum / 4;
    const puissance = 10 ** Math.floor(Math.log10(brut || 1));
    const pas = [1, 2, 2.5, 5, 10].map((m) => m * puissance).find((p) => p >= brut) ?? brut;
    const plafond = Math.ceil(maximum / pas) * pas;

    return Array.from({ length: Math.round(plafond / pas) + 1 }, (_, i) => i * pas);
}

/**
 * Courbe d'une seule série sur 30 jours : un trait de 2 px dans la couleur
 * d'accent, grille discrète, réticule et infobulle au survol, tableau des
 * valeurs pour les lecteurs d'écran et la consultation précise.
 */
export default function CourbeJournaliere({ titre, points, unite }: Props) {
    const conteneur = useRef<HTMLDivElement>(null);
    const [largeur, setLargeur] = useState(600);
    const [survol, setSurvol] = useState<number | null>(null);
    const idTitre = useId();

    useEffect(() => {
        const element = conteneur.current;
        if (!element) return;
        const observateur = new ResizeObserver(([entree]) => setLargeur(Math.max(280, entree.contentRect.width)));
        observateur.observe(element);

        return () => observateur.disconnect();
    }, []);

    const ticks = graduations(Math.max(...points.map((p) => p.valeur)));
    const plafond = ticks[ticks.length - 1] || 1;
    const zoneL = largeur - MARGE.gauche - MARGE.droite;
    const zoneH = HAUTEUR - MARGE.haut - MARGE.bas;
    const x = (i: number) => MARGE.gauche + (points.length > 1 ? (i / (points.length - 1)) * zoneL : zoneL / 2);
    const y = (v: number) => MARGE.haut + zoneH - (v / plafond) * zoneH;
    const trace = points.map((p, i) => `${i === 0 ? 'M' : 'L'}${x(i).toFixed(1)},${y(p.valeur).toFixed(1)}`).join(' ');
    const reperesX = [0, Math.floor((points.length - 1) / 2), points.length - 1];
    const actif = survol !== null ? points[survol] : null;

    const deplacer = (evenement: React.PointerEvent<SVGRectElement>) => {
        const zone = evenement.currentTarget.getBoundingClientRect();
        const ratio = (evenement.clientX - zone.left) / zone.width;
        setSurvol(Math.min(points.length - 1, Math.max(0, Math.round(ratio * (points.length - 1)))));
    };

    return (
        <figure aria-labelledby={idTitre} className="min-w-0">
            <figcaption id={idTitre} className="text-sm font-medium">
                {titre}
            </figcaption>

            <div ref={conteneur} className="relative mt-3">
                <svg width={largeur} height={HAUTEUR} role="img" aria-label={`${titre}, ${points.length} jours`} className="block">
                    {ticks.map((tick) => (
                        <g key={tick}>
                            <line x1={MARGE.gauche} x2={largeur - MARGE.droite} y1={y(tick)} y2={y(tick)} stroke="var(--color-line)" strokeWidth={1} />
                            <text x={MARGE.gauche - 8} y={y(tick)} dy="0.32em" textAnchor="end" className="fill-ink-faint text-[11px] tabular-nums">
                                {formaterNombre(tick)}
                            </text>
                        </g>
                    ))}
                    {reperesX.map((i) => (
                        <text
                            key={i}
                            x={x(i)}
                            y={HAUTEUR - 6}
                            textAnchor={i === 0 ? 'start' : i === points.length - 1 ? 'end' : 'middle'}
                            className="fill-ink-faint text-[11px]"
                        >
                            {jourCourt.format(dateDe(points[i].date))}
                        </text>
                    ))}

                    <path d={trace} fill="none" stroke="var(--color-accent)" strokeWidth={2} strokeLinejoin="round" strokeLinecap="round" />

                    {survol !== null && actif && (
                        <g pointerEvents="none">
                            <line x1={x(survol)} x2={x(survol)} y1={MARGE.haut} y2={MARGE.haut + zoneH} stroke="var(--color-ink-faint)" strokeWidth={1} />
                            <circle cx={x(survol)} cy={y(actif.valeur)} r={4.5} fill="var(--color-accent)" stroke="var(--color-canvas)" strokeWidth={2} />
                        </g>
                    )}

                    {/* Zone de survol plus grande que le tracé, sur toute la hauteur. */}
                    <rect
                        x={MARGE.gauche}
                        y={0}
                        width={zoneL}
                        height={HAUTEUR}
                        fill="transparent"
                        onPointerMove={deplacer}
                        onPointerDown={deplacer}
                        onPointerLeave={() => setSurvol(null)}
                    />
                </svg>

                {survol !== null && actif && (
                    <div
                        className="pointer-events-none absolute top-0 rounded border border-line bg-canvas px-2.5 py-1.5 text-xs whitespace-nowrap"
                        style={{
                            left: x(survol),
                            transform: `translateX(${survol > points.length / 2 ? 'calc(-100% - 10px)' : '10px'})`,
                        }}
                    >
                        <span className="block text-ink-muted first-letter:uppercase">{jourLong.format(dateDe(actif.date))}</span>
                        <span className="block font-medium tabular-nums">
                            {formaterNombre(actif.valeur)} {unite}
                        </span>
                    </div>
                )}
            </div>

            <details className="mt-2 text-xs">
                <summary className="cursor-pointer text-ink-muted hover:text-ink">Afficher les valeurs</summary>
                <table className="mt-2 w-full max-w-xs text-left">
                    <thead>
                        <tr className="text-ink-muted">
                            <th scope="col" className="py-1 font-medium">Jour</th>
                            <th scope="col" className="py-1 text-right font-medium">{unite}</th>
                        </tr>
                    </thead>
                    <tbody className="tabular-nums">
                        {points.map((point) => (
                            <tr key={point.date} className="border-t border-line">
                                <td className="py-1">{jourCourt.format(dateDe(point.date))}</td>
                                <td className="py-1 text-right">{formaterNombre(point.valeur)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </details>
        </figure>
    );
}
