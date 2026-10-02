import * as THREE from 'three';

/**
 * Modèles 3D de démonstration, générés à partir des dimensions de la pièce.
 *
 * Unités : 1 unité de scène = 1 mm. Axes : x = longueur, y = hauteur,
 * z = largeur. Chaque sous-partie est un THREE.Group nommé (« jante »,
 * « culasse »…) qui porte dans userData.direction son sens d'écartement pour
 * la vue éclatée. Les GLB réels suivent la même convention (voir
 * preparerGlb dans modele.ts).
 */

export type Dimensions = { longueur: number; largeur: number; hauteur: number };

type CleMateriau = 'metal' | 'sombre' | 'clair' | 'accent' | 'verre';

export type Materiaux = Record<CleMateriau, THREE.MeshStandardMaterial>;

export function creerMateriaux(): Materiaux {
    return {
        metal: new THREE.MeshStandardMaterial({ color: '#a3a9b1', metalness: 0.55, roughness: 0.35 }),
        sombre: new THREE.MeshStandardMaterial({ color: '#3a3f46', metalness: 0.1, roughness: 0.8 }),
        clair: new THREE.MeshStandardMaterial({ color: '#d4d8dd', metalness: 0.2, roughness: 0.6 }),
        accent: new THREE.MeshStandardMaterial({ color: '#3b5b7e', metalness: 0.3, roughness: 0.5 }),
        verre: new THREE.MeshStandardMaterial({ color: '#dfe8f1', metalness: 0, roughness: 0.1, transparent: true, opacity: 0.55 }),
    };
}

type Placement = {
    position?: [number, number, number];
    rotation?: [number, number, number];
    echelle?: [number, number, number];
};

function maillage(geometrie: THREE.BufferGeometry, materiau: THREE.Material, placement: Placement = {}): THREE.Mesh {
    const mesh = new THREE.Mesh(geometrie, materiau);
    if (placement.position) mesh.position.set(...placement.position);
    if (placement.rotation) mesh.rotation.set(...placement.rotation);
    if (placement.echelle) mesh.scale.set(...placement.echelle);

    return mesh;
}

function partie(nom: string, direction: [number, number, number], ...maillages: THREE.Mesh[]): THREE.Group {
    const groupe = new THREE.Group();
    groupe.name = nom;
    groupe.userData.direction = new THREE.Vector3(...direction);
    maillages.forEach((mesh) => {
        mesh.name = nom;
        groupe.add(mesh);
    });

    return groupe;
}

/** Cylindre couché le long de l'axe x. */
const AXE_X: [number, number, number] = [0, 0, Math.PI / 2];

class Helice extends THREE.Curve<THREE.Vector3> {
    constructor(
        private readonly rayon: number,
        private readonly hauteur: number,
        private readonly spires: number,
    ) {
        super();
    }

    getPoint(t: number, cible = new THREE.Vector3()): THREE.Vector3 {
        const angle = t * this.spires * Math.PI * 2;

        return cible.set(Math.cos(angle) * this.rayon, (t - 0.5) * this.hauteur, Math.sin(angle) * this.rayon);
    }
}

function roue({ longueur: L, largeur: W, hauteur: H }: Dimensions, m: Materiaux): THREE.Group[] {
    const R = Math.min(L, W) / 2;
    const tube = Math.min(H / 2, R * 0.28);
    const rayonJante = R - tube * 1.6;

    const branches = Array.from({ length: 5 }, (_, i) => {
        const angle = (i / 5) * Math.PI * 2;
        return maillage(new THREE.BoxGeometry(rayonJante * 0.9, H * 0.12, rayonJante * 0.14), m.metal, {
            position: [Math.cos(angle) * rayonJante * 0.45, H * 0.3, Math.sin(angle) * rayonJante * 0.45],
            rotation: [0, -angle, 0],
        });
    });

    return [
        partie('pneu', [0, -0.4, 0], maillage(new THREE.TorusGeometry(R - tube, tube, 24, 72), m.sombre, {
            rotation: [Math.PI / 2, 0, 0],
            echelle: [1, 1, H / (2 * tube)],
        })),
        partie('jante', [0, 0.6, 0],
            maillage(new THREE.CylinderGeometry(rayonJante, rayonJante, H * 0.5, 64), m.clair),
            ...branches,
        ),
        partie('écrou central', [0, 1.4, 0], maillage(new THREE.CylinderGeometry(R * 0.12, R * 0.12, H * 0.8, 6), m.accent)),
    ];
}

function moteur({ longueur: L, largeur: W, hauteur: H }: Dimensions, m: Materiaux): THREE.Group[] {
    const bas = -H / 2;
    const r = Math.min(L, W);

    return [
        partie('bloc', [0, -0.4, 0],
            maillage(new THREE.BoxGeometry(L * 0.9, H * 0.55, W * 0.85), m.clair, { position: [0, bas + H * 0.275, 0] }),
            maillage(new THREE.CylinderGeometry(H * 0.12, H * 0.12, L * 0.08, 32), m.metal, {
                position: [L * 0.47, bas + H * 0.2, 0],
                rotation: AXE_X,
            }),
        ),
        partie('culasse', [0, 0.3, 0],
            maillage(new THREE.BoxGeometry(L * 0.95, H * 0.15, W * 0.75), m.metal, { position: [0, bas + H * 0.625, 0] }),
        ),
        partie('carburateur', [0, 0.8, 0],
            maillage(new THREE.CylinderGeometry(r * 0.1, r * 0.12, H * 0.12, 32), m.accent, { position: [0, bas + H * 0.76, 0] }),
        ),
        partie('filtre à air', [0, 1.3, 0],
            maillage(new THREE.CylinderGeometry(r * 0.32, r * 0.32, H * 0.12, 48), m.sombre, { position: [0, bas + H * 0.88, 0] }),
        ),
    ];
}

function siege({ longueur: L, largeur: W, hauteur: H }: Dimensions, m: Materiaux): THREE.Group[] {
    const bas = -H / 2;

    return [
        partie('assise', [0, -0.6, 0],
            maillage(new THREE.BoxGeometry(L, H * 0.14, W), m.sombre, { position: [0, bas + H * 0.07, 0] }),
        ),
        partie('dossier', [-0.8, 0.2, 0],
            maillage(new THREE.BoxGeometry(L * 0.16, H * 0.86, W * 0.95), m.sombre, {
                position: [-L / 2 + L * 0.1, bas + H * 0.57, 0],
                rotation: [0, 0, -0.08],
            }),
        ),
        partie('harnais', [0.9, 0.3, 0],
            ...[-1, 1].map((cote) =>
                maillage(new THREE.BoxGeometry(L * 0.03, H * 0.62, W * 0.08), m.accent, {
                    position: [-L / 2 + L * 0.2, bas + H * 0.52, cote * W * 0.2],
                    rotation: [0, 0, -0.08],
                }),
            ),
            maillage(new THREE.BoxGeometry(L * 0.6, H * 0.03, W * 0.7), m.accent, { position: [L * 0.05, bas + H * 0.155, 0] }),
        ),
    ];
}

function echappement({ longueur: L, largeur: W, hauteur: H }: Dimensions, m: Materiaux): THREE.Group[] {
    const g = -L / 2;
    const rTube = Math.min(H, W) * 0.09;
    const tubes = [-0.36, -0.12, 0.12, 0.36].map((dz) =>
        maillage(new THREE.CylinderGeometry(rTube, rTube, L * 0.16, 16), m.metal, {
            position: [g + L * 0.08, 0, dz * W],
            rotation: AXE_X,
        }),
    );
    const longueurConduit = L * 0.33;

    return [
        partie('collecteur', [-0.6, 0, 0],
            ...tubes,
            maillage(new THREE.CylinderGeometry(H * 0.16, W * 0.45, L * 0.05, 32), m.metal, {
                position: [g + L * 0.185, 0, 0],
                rotation: [0, 0, -Math.PI / 2],
            }),
            maillage(new THREE.CylinderGeometry(H * 0.16, H * 0.16, longueurConduit, 24), m.metal, {
                position: [g + L * 0.21 + longueurConduit / 2, 0, 0],
                rotation: AXE_X,
            }),
        ),
        partie('silencieux', [0, 0.4, 0],
            maillage(new THREE.CylinderGeometry(H / 2, H / 2, L * 0.34, 48), m.clair, {
                position: [L / 2 - L * 0.27, 0, 0],
                rotation: AXE_X,
                echelle: [1, 1, Math.min(W / H, 1.6)],
            }),
        ),
        partie('sortie', [0.7, 0, 0],
            maillage(new THREE.CylinderGeometry(H * 0.24, H * 0.2, L * 0.1, 32), m.accent, {
                position: [L / 2 - L * 0.05, 0, 0],
                rotation: AXE_X,
            }),
        ),
    ];
}

function aileron({ longueur: L, largeur: W, hauteur: H }: Dimensions, m: Materiaux): THREE.Group[] {
    const bas = -H / 2;

    return [
        partie('lame', [0, 0.8, 0],
            maillage(new THREE.BoxGeometry(L * 0.94, H * 0.12, W * 0.9), m.sombre, {
                position: [0, H / 2 - H * 0.12, 0],
                rotation: [0.12, 0, 0],
            }),
        ),
        ...[-1, 1].map((cote) =>
            partie('dérives', [cote * 0.7, 0.3, 0],
                maillage(new THREE.BoxGeometry(L * 0.025, H * 0.45, W), m.clair, {
                    position: [cote * (L / 2 - L * 0.0125), H / 2 - H * 0.225, 0],
                }),
            ),
        ),
        partie('supports', [0, -0.6, 0],
            ...[-1, 1].map((cote) =>
                maillage(new THREE.BoxGeometry(L * 0.04, H * 0.76, W * 0.28), m.metal, {
                    position: [cote * L * 0.18, bas + H * 0.38, 0],
                }),
            ),
        ),
    ];
}

function volant({ longueur: L, largeur: W, hauteur: H }: Dimensions, m: Materiaux): THREE.Group[] {
    const R = Math.min(L, W) / 2;
    const tube = Math.min(H / 2, R * 0.09);
    const rayon = R - tube;

    return [
        partie('couronne', [0, 0.7, 0],
            maillage(new THREE.TorusGeometry(rayon, tube, 20, 96), m.sombre, { rotation: [Math.PI / 2, 0, 0] }),
        ),
        partie('branches', [0, 0.25, 0],
            ...[90, 210, 330].map((degres) => {
                const angle = THREE.MathUtils.degToRad(degres);
                return maillage(new THREE.BoxGeometry(rayon, tube * 0.7, tube * 1.4), m.metal, {
                    position: [Math.cos(angle) * rayon / 2, -tube * 0.3, Math.sin(angle) * rayon / 2],
                    rotation: [0, -angle, 0],
                });
            }),
        ),
        partie('moyeu', [0, -0.9, 0],
            maillage(new THREE.CylinderGeometry(R * 0.22, R * 0.26, H * 0.9, 32), m.accent, { position: [0, -H * 0.05, 0] }),
        ),
    ];
}

function optique({ longueur: L, largeur: W, hauteur: H }: Dimensions, m: Materiaux): THREE.Group[] {
    const r = Math.min(H, W) / 2;
    const reflecteur = new THREE.SphereGeometry(r * 0.82, 40, 20, 0, Math.PI * 2, 0, Math.PI / 2);

    return [
        partie('boîtier', [-0.7, 0, 0],
            maillage(new THREE.CylinderGeometry(r, r * 0.85, L * 0.78, 48, 1, true), m.sombre, {
                position: [-L / 2 + L * 0.39, 0, 0],
                rotation: [0, 0, -Math.PI / 2],
                echelle: [1, 1, W / H],
            }),
            maillage(new THREE.CircleGeometry(r * 0.85, 48), m.sombre, {
                position: [-L / 2, 0, 0],
                rotation: [0, -Math.PI / 2, 0],
                echelle: [W / H, 1, 1],
            }),
        ),
        partie('réflecteur', [0.15, 0, 0],
            maillage(reflecteur, m.metal, {
                position: [L / 2 - L * 0.12, 0, 0],
                rotation: [0, 0, Math.PI / 2],
                echelle: [1, 1, W / H],
            }),
        ),
        partie('lentille', [0.9, 0, 0],
            maillage(new THREE.CylinderGeometry(r * 0.98, r * 0.98, L * 0.08, 48), m.verre, {
                position: [L / 2 - L * 0.04, 0, 0],
                rotation: AXE_X,
                echelle: [1, 1, W / H],
            }),
        ),
    ];
}

function suspension({ longueur: L, largeur: W, hauteur: H }: Dimensions, m: Materiaux): THREE.Group[] {
    const r = Math.min(L, W) / 2;

    return [
        partie('amortisseur', [0, 0, 0],
            maillage(new THREE.CylinderGeometry(r * 0.28, r * 0.28, H * 0.62, 32), m.sombre, { position: [0, -H * 0.12, 0] }),
            maillage(new THREE.CylinderGeometry(r * 0.12, r * 0.12, H * 0.34, 16), m.metal, { position: [0, H * 0.33, 0] }),
        ),
        partie('ressort', [1, 0, 0],
            maillage(new THREE.TubeGeometry(new Helice(r * 0.72, H * 0.66, 7), 280, r * 0.08, 10), m.accent),
        ),
        partie('coupelle', [0, 0.7, 0],
            maillage(new THREE.CylinderGeometry(r, r, H * 0.03, 48), m.clair, { position: [0, H * 0.35, 0] }),
        ),
        partie('coupelle', [0, -0.7, 0],
            maillage(new THREE.CylinderGeometry(r, r, H * 0.03, 48), m.clair, { position: [0, -H * 0.35, 0] }),
        ),
    ];
}

const FORMES: Record<string, (dimensions: Dimensions, materiaux: Materiaux) => THREE.Group[]> = {
    roue,
    moteur,
    siege,
    echappement,
    aileron,
    volant,
    optique,
    suspension,
};

/**
 * Assemble la forme d'une catégorie. Une forme inconnue donne un simple
 * parallélépipède aux dimensions de la pièce.
 */
export function creerFormeProcedurale(forme: string, dimensions: Dimensions, materiaux: Materiaux): THREE.Group {
    const modele = new THREE.Group();
    const construire = FORMES[forme];
    const parties = construire
        ? construire(dimensions, materiaux)
        : [partie('pièce', [0, 0, 0], maillage(new THREE.BoxGeometry(dimensions.longueur, dimensions.hauteur, dimensions.largeur), materiaux.clair))];
    parties.forEach((p) => modele.add(p));

    return modele;
}
