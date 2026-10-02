import { Grid, Html, Line, OrbitControls, useGLTF } from '@react-three/drei';
import { Canvas, useThree, type ThreeEvent } from '@react-three/fiber';
import { Component, Suspense, useEffect, useId, useMemo, useState, type ReactNode } from 'react';
import * as THREE from 'three';
import { formaterMm } from '@/lib/catalogue';
import type { FichePiece } from '@/types/piece';
import { creerFormeProcedurale, creerMateriaux, type Dimensions } from './formes';
import {
    appliquerEclatement,
    exporterStl,
    liberer,
    pasGrille,
    preparerGlb,
    preparerProcedural,
    taillePiece,
    type ModelePrepare,
} from './modele';

export type ExportStl = () => Blob;

type Props = {
    piece: FichePiece;
    onExportPret: (exporter: ExportStl | null) => void;
};

type EtatScene = {
    eclatement: number;
    mesureActive: boolean;
    points: THREE.Vector3[];
    cotesVisibles: boolean;
    reinitialisation: number;
    positionCamera: [number, number, number];
    onPoint: (point: THREE.Vector3) => void;
    onParties: (noms: string[]) => void;
    onExportPret: (exporter: ExportStl | null) => void;
};

const COULEUR_ACCENT = '#3b5b7e';
const COULEUR_COTE = '#59616b';

export default function Visionneuse({ piece, onExportPret }: Props) {
    const dimensions = piece.dimensions;
    const taille = taillePiece(dimensions);
    const { pas } = pasGrille(taille);
    const [eclatement, setEclatement] = useState(0);
    const [mesureActive, setMesureActive] = useState(false);
    const [points, setPoints] = useState<THREE.Vector3[]>([]);
    const [cotesVisibles, setCotesVisibles] = useState(true);
    const [reinitialisation, setReinitialisation] = useState(0);
    const [parties, setParties] = useState<string[]>([]);
    const [erreurGlb, setErreurGlb] = useState(false);
    const idEclatement = useId();

    const distance = points.length === 2 ? points[0].distanceTo(points[1]) : null;
    const distanceCamera = taille * 2.6;
    const positionCamera: [number, number, number] = [distanceCamera * 0.75, distanceCamera * 0.6, distanceCamera * 0.9];

    const etat: EtatScene = {
        eclatement,
        mesureActive,
        points,
        cotesVisibles,
        reinitialisation,
        positionCamera,
        onPoint: (point) => setPoints((actuels) => (actuels.length >= 2 ? [point] : [...actuels, point])),
        onParties: setParties,
        onExportPret,
    };

    return (
        <div className="flex flex-col overflow-hidden rounded-md border border-line bg-surface">
            <div className={`relative aspect-[4/3] ${mesureActive ? 'cursor-crosshair' : 'cursor-grab active:cursor-grabbing'}`}>
                <Canvas
                    frameloop="demand"
                    dpr={[1, 2]}
                    camera={{
                        fov: 35,
                        near: taille / 500,
                        far: taille * 200,
                        position: positionCamera,
                    }}
                    aria-label={`Modèle 3D de ${piece.nom}`}
                    fallback={
                        <p className="p-6 text-sm text-ink-muted">
                            Votre navigateur ne prend pas en charge WebGL : la visionneuse 3D est indisponible.
                        </p>
                    }
                >
                    <color attach="background" args={['#f6f7f9']} />
                    <hemisphereLight args={['#ffffff', '#c9ced5', 1.1]} />
                    <directionalLight position={[taille, taille * 2, taille * 1.5]} intensity={1.6} />
                    <directionalLight position={[-taille, taille, -taille]} intensity={0.5} />

                    {piece.modele_3d && !erreurGlb ? (
                        <BarriereGlb onErreur={() => setErreurGlb(true)}>
                            <Suspense fallback={null}>
                                <ModeleGlb url={piece.modele_3d} dimensions={dimensions} etat={etat} />
                            </Suspense>
                        </BarriereGlb>
                    ) : (
                        <ModeleProcedural forme={piece.categorie.forme} dimensions={dimensions} etat={etat} />
                    )}

                    <GrilleGraduee taille={taille} />
                    {cotesVisibles && <Cotes dimensions={dimensions} />}
                    <Mesure points={points} taille={taille} />
                </Canvas>

                <div className="pointer-events-none absolute left-3 top-3 flex flex-col gap-1 text-xs">
                    <span className="rounded bg-canvas/90 px-2 py-1 font-mono text-ink">
                        {formaterMm(dimensions.longueur)} × {formaterMm(dimensions.largeur)} × {formaterMm(dimensions.hauteur)}
                    </span>
                    <span className="self-start rounded bg-canvas/90 px-2 py-1 text-ink-muted">
                        Grille : 1 carreau = {formaterMm(pas)}
                    </span>
                </div>

                {piece.modele_3d && !erreurGlb && parties.length === 0 && (
                    <p className="pointer-events-none absolute inset-0 flex items-center justify-center text-sm text-ink-muted">
                        Chargement du modèle…
                    </p>
                )}

                {mesureActive && (
                    <div className="pointer-events-none absolute inset-x-3 bottom-3 flex justify-center">
                        <p className="rounded bg-canvas/95 px-3 py-1.5 text-sm" aria-live="polite">
                            {distance !== null ? (
                                <>
                                    Distance : <strong className="font-semibold tabular-nums">{formaterMm(Number(distance.toFixed(2)))}</strong>
                                </>
                            ) : points.length === 1 ? (
                                'Cliquez un second point sur la pièce'
                            ) : (
                                'Cliquez un premier point sur la pièce'
                            )}
                        </p>
                    </div>
                )}
            </div>

            <div className="flex flex-col gap-3 border-t border-line bg-canvas p-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
                <div className="flex items-center gap-3">
                    <label htmlFor={idEclatement} className="shrink-0 text-sm text-ink-muted">
                        Vue éclatée
                    </label>
                    <input
                        id={idEclatement}
                        type="range"
                        min={0}
                        max={1}
                        step={0.01}
                        value={eclatement}
                        onChange={(e) => {
                            setEclatement(Number(e.target.value));
                            setPoints([]);
                        }}
                        aria-valuetext={`${Math.round(eclatement * 100)} %`}
                        className="w-full accent-accent sm:w-40"
                    />
                </div>
                <div className="flex flex-wrap gap-2">
                    <button
                        type="button"
                        aria-pressed={mesureActive}
                        onClick={() => {
                            setMesureActive(!mesureActive);
                            setPoints([]);
                        }}
                        className={`h-8 rounded border px-3 text-sm ${
                            mesureActive ? 'border-accent bg-accent-soft text-accent-strong' : 'border-line-strong hover:bg-surface'
                        }`}
                    >
                        Mesurer
                    </button>
                    <button
                        type="button"
                        aria-pressed={cotesVisibles}
                        onClick={() => setCotesVisibles(!cotesVisibles)}
                        className={`h-8 rounded border px-3 text-sm ${
                            cotesVisibles ? 'border-accent bg-accent-soft text-accent-strong' : 'border-line-strong hover:bg-surface'
                        }`}
                    >
                        Cotes
                    </button>
                    <button
                        type="button"
                        onClick={() => {
                            setReinitialisation((n) => n + 1);
                            setEclatement(0);
                            setPoints([]);
                        }}
                        className="h-8 rounded border border-line-strong px-3 text-sm hover:bg-surface"
                    >
                        Réinitialiser la vue
                    </button>
                </div>
            </div>

            {(parties.length > 0 || erreurGlb) && (
                <p className="border-t border-line bg-canvas px-3 py-2 text-xs text-ink-muted">
                    {erreurGlb && 'Le fichier 3D n’a pas pu être chargé : affichage du modèle de démonstration. '}
                    {parties.length > 0 && <>Sous-parties : {parties.join(', ')}</>}
                </p>
            )}
        </div>
    );
}

function ModeleProcedural({ forme, dimensions, etat }: { forme: string; dimensions: Dimensions; etat: EtatScene }) {
    const modele = useMemo(() => {
        const materiaux = creerMateriaux();
        return { prepare: preparerProcedural(creerFormeProcedurale(forme, dimensions, materiaux)), materiaux };
    }, [forme, dimensions]);

    useEffect(
        () => () => {
            liberer(modele.prepare.objet);
            Object.values(modele.materiaux).forEach((materiau) => materiau.dispose());
        },
        [modele],
    );

    return <ContenuScene modele={modele.prepare} taille={taillePiece(dimensions)} etat={etat} />;
}

function ModeleGlb({ url, dimensions, etat }: { url: string; dimensions: Dimensions; etat: EtatScene }) {
    // Draco et Meshopt désactivés : leurs décodeurs viendraient d'un CDN externe.
    const { scene } = useGLTF(url, false, false);
    const modele = useMemo(() => preparerGlb(scene, dimensions), [scene, dimensions]);

    return <ContenuScene modele={modele} taille={taillePiece(dimensions)} etat={etat} />;
}

type Controles = { target: THREE.Vector3; update: () => void };

function ContenuScene({ modele, taille, etat }: { modele: ModelePrepare; taille: number; etat: EtatScene }) {
    const invalidate = useThree((s) => s.invalidate);
    const camera = useThree((s) => s.camera);
    const controles = useThree((s) => s.controls) as unknown as Controles | null;
    // Calculée au repos, avant toute vue éclatée : la cible de la caméra ne bouge plus ensuite.
    const cible = useMemo<[number, number, number]>(
        () => [0, new THREE.Box3().setFromObject(modele.objet).getSize(new THREE.Vector3()).y / 2, 0],
        [modele],
    );
    const { onParties, onExportPret, reinitialisation, positionCamera } = etat;

    useEffect(() => {
        onParties([...new Set(modele.parties.map((partie) => partie.name).filter(Boolean))]);
        onExportPret(() => exporterStl(modele));

        return () => onExportPret(null);
    }, [modele, onParties, onExportPret]);

    useEffect(() => {
        appliquerEclatement(modele, etat.eclatement, taille);
        invalidate();
    }, [modele, etat.eclatement, taille, invalidate]);

    useEffect(() => {
        if (reinitialisation === 0 || !controles) return;
        camera.position.set(...positionCamera);
        controles.target.set(...cible);
        controles.update();
        invalidate();
    }, [reinitialisation]);

    const cliquer = (evenement: ThreeEvent<MouseEvent>) => {
        // Un glisser pour tourner la vue ne doit pas poser de point.
        if (!etat.mesureActive || evenement.delta > 4) return;
        evenement.stopPropagation();
        etat.onPoint(evenement.point.clone());
    };

    return (
        <>
            <primitive object={modele.objet} onClick={cliquer} />
            <OrbitControls
                makeDefault
                enableDamping
                target={cible}
                minDistance={taille * 0.4}
                maxDistance={taille * 10}
                maxPolarAngle={Math.PI * 0.95}
            />
        </>
    );
}

function GrilleGraduee({ taille }: { taille: number }) {
    const { pas, section } = pasGrille(taille);
    const etendue = Math.ceil((taille * 3) / section) * section;
    const graduations = Math.floor(etendue / 2 / section);
    const decalage = taille * 0.9;

    return (
        <>
            <Grid
                position={[0, -taille / 2000, 0]}
                args={[etendue, etendue]}
                cellSize={pas}
                cellThickness={0.8}
                cellColor="#c6ccd3"
                sectionSize={section}
                sectionThickness={1.2}
                sectionColor="#959ea8"
                fadeDistance={etendue * 1.5}
                fadeStrength={1}
            />
            {Array.from({ length: graduations * 2 + 1 }, (_, i) => (i - graduations) * section).map((x) => (
                <Html key={x} position={[x, 0, decalage]} center style={{ pointerEvents: 'none' }} zIndexRange={[10, 0]}>
                    <span className="whitespace-nowrap text-[10px] text-ink-faint tabular-nums">{formaterMm(x)}</span>
                </Html>
            ))}
        </>
    );
}

function Cotes({ dimensions }: { dimensions: Dimensions }) {
    const { longueur: L, largeur: W, hauteur: H } = dimensions;
    const ecart = taillePiece(dimensions) * 0.12;
    const cotes: { de: [number, number, number]; a: [number, number, number]; valeur: number }[] = [
        { de: [-L / 2, 0, W / 2 + ecart], a: [L / 2, 0, W / 2 + ecart], valeur: L },
        { de: [L / 2 + ecart, 0, -W / 2], a: [L / 2 + ecart, 0, W / 2], valeur: W },
        { de: [-L / 2 - ecart, 0, W / 2], a: [-L / 2 - ecart, H, W / 2], valeur: H },
    ];

    return (
        <>
            {cotes.map(({ de, a, valeur }, i) => (
                <group key={i}>
                    <Line points={[de, a]} color={COULEUR_COTE} lineWidth={1} />
                    <Html
                        position={[(de[0] + a[0]) / 2, (de[1] + a[1]) / 2, (de[2] + a[2]) / 2]}
                        center
                        style={{ pointerEvents: 'none' }}
                        zIndexRange={[20, 10]}
                    >
                        <span className="whitespace-nowrap rounded bg-canvas/90 px-1 text-[11px] text-ink tabular-nums">
                            {formaterMm(valeur)}
                        </span>
                    </Html>
                </group>
            ))}
        </>
    );
}

function Mesure({ points, taille }: { points: THREE.Vector3[]; taille: number }) {
    if (points.length === 0) return null;
    const rayon = taille * 0.012;

    return (
        <>
            {points.map((point, i) => (
                <mesh key={i} position={point}>
                    <sphereGeometry args={[rayon, 16, 16]} />
                    <meshBasicMaterial color={COULEUR_ACCENT} depthTest={false} />
                </mesh>
            ))}
            {points.length === 2 && (
                <>
                    <Line points={points} color={COULEUR_ACCENT} lineWidth={2} depthTest={false} />
                    <Html position={points[0].clone().lerp(points[1], 0.5)} center style={{ pointerEvents: 'none' }}>
                        <span className="whitespace-nowrap rounded bg-accent px-1.5 py-0.5 text-[11px] font-medium text-white tabular-nums">
                            {formaterMm(Number(points[0].distanceTo(points[1]).toFixed(2)))}
                        </span>
                    </Html>
                </>
            )}
        </>
    );
}

class BarriereGlb extends Component<{ children: ReactNode; onErreur: () => void }, { erreur: boolean }> {
    state = { erreur: false };

    static getDerivedStateFromError() {
        return { erreur: true };
    }

    componentDidCatch() {
        this.props.onErreur();
    }

    render() {
        return this.state.erreur ? null : this.props.children;
    }
}
