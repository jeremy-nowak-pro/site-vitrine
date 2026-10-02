import * as THREE from 'three';
import { STLExporter } from 'three/examples/jsm/exporters/STLExporter.js';
import type { Dimensions } from './formes';

export type ModelePrepare = {
    /** Racine posée au sol (y = 0) et centrée en x et z. */
    objet: THREE.Group;
    /** Sous-parties déplacées par la vue éclatée. */
    parties: THREE.Object3D[];
};

const ECART_MAX = 0.4;

export function taillePiece(dimensions: Dimensions): number {
    return Math.max(dimensions.longueur, dimensions.largeur, dimensions.hauteur);
}

/**
 * Pose le modèle au sol et mémorise la position de repos de chaque partie.
 */
function poserAuSol(contenu: THREE.Object3D): THREE.Group {
    const racine = new THREE.Group();
    racine.add(contenu);
    racine.updateMatrixWorld(true);

    const boite = new THREE.Box3().setFromObject(contenu);
    const centre = boite.getCenter(new THREE.Vector3());
    contenu.position.set(-centre.x, -boite.min.y, -centre.z);
    racine.updateMatrixWorld(true);

    return racine;
}

function memoriserRepos(parties: THREE.Object3D[]): void {
    parties.forEach((partie) => {
        partie.userData.repos = partie.position.clone();
    });
}

export function preparerProcedural(modele: THREE.Group): ModelePrepare {
    const objet = poserAuSol(modele);
    const parties = [...modele.children];
    memoriserRepos(parties);

    return { objet, parties };
}

/**
 * Prépare un GLB réel : mise à l'échelle sur la plus grande dimension de la
 * pièce (le fichier peut être exporté en mètres ou en millimètres), puis
 * calcul d'une direction d'éclatement par maillage, du centre du modèle vers
 * le centre du maillage.
 */
export function preparerGlb(scene: THREE.Object3D, dimensions: Dimensions): ModelePrepare {
    const contenu = scene.clone(true);
    const boite = new THREE.Box3().setFromObject(contenu);
    const plusGrandCote = Math.max(...boite.getSize(new THREE.Vector3()).toArray());
    if (plusGrandCote > 0) {
        contenu.scale.multiplyScalar(taillePiece(dimensions) / plusGrandCote);
    }

    const objet = poserAuSol(contenu);
    const centreModele = new THREE.Box3().setFromObject(objet).getCenter(new THREE.Vector3());
    const parties: THREE.Object3D[] = [];

    objet.traverse((enfant) => {
        if (!(enfant instanceof THREE.Mesh) || !enfant.parent) return;
        const centre = new THREE.Box3().setFromObject(enfant).getCenter(new THREE.Vector3());
        const directionMonde = centre.sub(centreModele);
        if (directionMonde.lengthSq() === 0) directionMonde.set(0, 1, 0);
        directionMonde.normalize();
        // La position d'un maillage s'exprime dans le repère de son parent.
        const inverseParent = new THREE.Matrix4().copy(enfant.parent.matrixWorld).invert();
        const echelleParent = new THREE.Vector3().setFromMatrixScale(enfant.parent.matrixWorld).x || 1;
        enfant.userData.direction = directionMonde.transformDirection(inverseParent).divideScalar(echelleParent);
        parties.push(enfant);
    });
    memoriserRepos(parties);

    return { objet, parties };
}

/**
 * facteur : 0 (assemblé) à 1 (éclaté). L’écart maximal vaut ~40 % de la
 * plus grande dimension de la pièce.
 */
export function appliquerEclatement(modele: ModelePrepare, facteur: number, taille: number): void {
    modele.parties.forEach((partie) => {
        const repos = partie.userData.repos as THREE.Vector3;
        const direction = partie.userData.direction as THREE.Vector3 | undefined;
        partie.position.copy(repos);
        if (direction) {
            partie.position.addScaledVector(direction, facteur * taille * ECART_MAX);
        }
    });
    modele.objet.updateMatrixWorld(true);
}

/**
 * Pas de grille « rond » (1, 2 ou 5 × 10ⁿ mm) donnant une dizaine de
 * carreaux sur la plus grande dimension de la pièce. Les traits forts
 * tombent tous les 5 ou 10 carreaux selon le pas : 5, 10, 50, 100 mm…
 */
export function pasGrille(taille: number): { pas: number; section: number } {
    const cible = taille / 12;
    const puissance = 10 ** Math.floor(Math.log10(cible));
    const mantisse = cible / puissance;
    const base = mantisse < 1.5 ? 1 : mantisse < 3.5 ? 2 : mantisse < 7.5 ? 5 : 10;
    const pas = base * puissance;

    return { pas, section: base === 5 || base === 10 ? pas * 2 : pas * 5 };
}

/**
 * STL binaire du modèle tel qu'il est affiché (vue éclatée comprise), en mm.
 */
export function exporterStl(modele: ModelePrepare): Blob {
    modele.objet.updateMatrixWorld(true);
    const donnees = new STLExporter().parse(modele.objet, { binary: true });

    return new Blob([donnees], { type: 'model/stl' });
}

export function liberer(objet: THREE.Object3D): void {
    objet.traverse((enfant) => {
        if (enfant instanceof THREE.Mesh) {
            enfant.geometry.dispose();
        }
    });
}
