/** Infinite 3D Gallery — port 3d-gallery-photography.tsx (21st.dev), vanilla Three.js */
const SHOWCASE_CANDIDATES = [
  { src: "./assets/showcase-saggita.png", alt: "Saggita Residencial — desarrollo web" },
  { src: "./assets/showcase-cumbre.png", alt: "La Cumbre Juriquilla — landing inmobiliaria" },
  { src: "./assets/showcase-aduanero.png", alt: "Sistema Aduanero — plataforma a medida" },
  { src: "./assets/showcase-wallet.png", alt: "App wallet financiera" },
  { src: "./assets/showcase-calendar.png", alt: "App de calendario y organización" },
  { src: "./assets/mockup-web-baikal.png", alt: "Cañadas El Mirador — desarrollo web" },
  { src: "./assets/mockup-web-renathya.png", alt: "Renathya Elite Women — sitio corporativo" },
  { src: "./assets/mockup-web-candas.png", alt: "Cañadas — landing inmobiliaria" },
  { src: "./assets/mockup-brand-falcon.png", alt: "Falcon Sheet Metal — branding" },
  { src: "./assets/mockup-mobile-app.png", alt: "App móvil — e-commerce" },
];

function probeImage(src) {
  return new Promise((resolve) => {
    const img = new Image();
    img.onload = () => resolve(true);
    img.onerror = () => resolve(false);
    img.src = src;
  });
}

async function resolveShowcaseImages() {
  const checks = await Promise.all(
    SHOWCASE_CANDIDATES.map(async (item) => ((await probeImage(item.src)) ? item : null))
  );
  return checks.filter(Boolean);
}

const DEPTH_RANGE = 50;
const MAX_H = 8;
const MAX_V = 8;

const FADE = {
  fadeIn: { start: 0.05, end: 0.25 },
  fadeOut: { start: 0.4, end: 0.43 },
};

const BLUR = {
  blurIn: { start: 0, end: 0.1 },
  blurOut: { start: 0.4, end: 0.43 },
  maxBlur: 8,
};

function createClothMaterial(THREE) {
  return new THREE.ShaderMaterial({
    transparent: true,
    uniforms: {
      map: { value: null },
      opacity: { value: 1 },
      blurAmount: { value: 0 },
      scrollForce: { value: 0 },
      time: { value: 0 },
      isHovered: { value: 0 },
    },
    vertexShader: `
      uniform float scrollForce;
      uniform float time;
      uniform float isHovered;
      varying vec2 vUv;
      void main() {
        vUv = uv;
        vec3 pos = position;
        float curveIntensity = scrollForce * 0.3;
        float dist = length(pos.xy);
        float curve = dist * dist * curveIntensity;
        float ripple1 = sin(pos.x * 2.0 + scrollForce * 3.0) * 0.02;
        float ripple2 = sin(pos.y * 2.5 + scrollForce * 2.0) * 0.015;
        float clothEffect = (ripple1 + ripple2) * abs(curveIntensity) * 2.0;
        float flagWave = 0.0;
        if (isHovered > 0.5) {
          float wavePhase = pos.x * 3.0 + time * 8.0;
          float damp = smoothstep(-0.5, 0.5, pos.x);
          flagWave = sin(wavePhase) * 0.1 * damp;
          flagWave += sin(pos.x * 5.0 + time * 12.0) * 0.03 * damp;
        }
        pos.z -= (curve + clothEffect + flagWave);
        gl_Position = projectionMatrix * modelViewMatrix * vec4(pos, 1.0);
      }
    `,
    fragmentShader: `
      uniform sampler2D map;
      uniform float opacity;
      uniform float blurAmount;
      uniform float scrollForce;
      varying vec2 vUv;
      void main() {
        vec4 color = texture2D(map, vUv);
        if (blurAmount > 0.0) {
          vec2 texel = 1.0 / vec2(textureSize(map, 0));
          vec4 blurred = vec4(0.0);
          float total = 0.0;
          for (float x = -2.0; x <= 2.0; x += 1.0) {
            for (float y = -2.0; y <= 2.0; y += 1.0) {
              vec2 offset = vec2(x, y) * texel * blurAmount;
              float w = 1.0 / (1.0 + length(vec2(x, y)));
              blurred += texture2D(map, vUv + offset) * w;
              total += w;
            }
          }
          color = blurred / total;
        }
        float curveHighlight = abs(scrollForce) * 0.05;
        color.rgb += vec3(curveHighlight * 0.1);
        gl_FragColor = vec4(color.rgb, color.a * opacity);
      }
    `,
  });
}

function calcOpacity(t, fade) {
  if (t < fade.fadeIn.start) return 0;
  if (t <= fade.fadeIn.end) return (t - fade.fadeIn.start) / (fade.fadeIn.end - fade.fadeIn.start);
  if (t < fade.fadeOut.start) return 1;
  if (t <= fade.fadeOut.end) return 1 - (t - fade.fadeOut.start) / (fade.fadeOut.end - fade.fadeOut.start);
  return 0;
}

function calcBlur(t, blur) {
  if (t < blur.blurIn.start) return blur.maxBlur;
  if (t <= blur.blurIn.end) {
    const p = (t - blur.blurIn.start) / (blur.blurIn.end - blur.blurIn.start);
    return blur.maxBlur * (1 - p);
  }
  if (t < blur.blurOut.start) return 0;
  if (t <= blur.blurOut.end) {
    const p = (t - blur.blurOut.start) / (blur.blurOut.end - blur.blurOut.start);
    return blur.maxBlur * p;
  }
  return blur.maxBlur;
}

function buildSpatialPositions(count) {
  const positions = [];
  for (let i = 0; i < count; i += 1) {
    const ha = (i * 2.618) % (Math.PI * 2);
    const va = (i * 1.618 + Math.PI / 3) % (Math.PI * 2);
    const hr = (i % 3) * 1.2;
    const vr = ((i + 1) % 4) * 0.8;
    positions.push({
      x: (Math.sin(ha) * hr * MAX_H) / 3,
      y: (Math.cos(va) * vr * MAX_V) / 4,
    });
  }
  return positions;
}

function renderFallback(root, images) {
  const wrap = root.querySelector(".showcase-gallery__fallback");
  if (!wrap) return;
  wrap.hidden = false;
  wrap.innerHTML = images
    .map(
      (img) =>
        `<figure class="showcase-gallery__fallback-item"><img src="${img.src}" alt="${img.alt}" loading="lazy" /></figure>`
    )
    .join("");
  root.classList.add("is-fallback");
  root.classList.add("is-ready");
}

export async function initInfiniteGallery3d() {
  const root = document.querySelector("[data-infinite-gallery]");
  const canvas = root?.querySelector(".showcase-gallery__canvas");
  if (!root || !canvas) return;

  const images = await resolveShowcaseImages();
  if (!images.length) return;

  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (reducedMotion) {
    renderFallback(root, images);
    return;
  }

  initWebGLGallery(root, canvas, images).catch((err) => {
    console.warn("Showcase 3D fallback:", err);
    renderFallback(root, images);
  });
}

async function initWebGLGallery(root, canvas, images) {
  const THREE = await import("https://cdn.jsdelivr.net/npm/three@0.171.0/build/three.module.js");

  const loader = new THREE.TextureLoader();
  const textures = (
    await Promise.all(
      images.map(
        (img) =>
          new Promise((resolve) => {
            loader.load(
              img.src,
              (tex) => {
                tex.colorSpace = THREE.SRGBColorSpace;
                resolve({ tex, alt: img.alt, src: img.src });
              },
              undefined,
              () => resolve(null)
            );
          })
      )
    )
  ).filter(Boolean);

  if (!textures.length) throw new Error("No showcase textures loaded");

  const visibleCount = Math.min(12, Math.max(8, textures.length + 3));
  const speed = 1.2;
  const spatial = buildSpatialPositions(visibleCount);
  const totalImages = textures.length;

  const renderer = new THREE.WebGLRenderer({
    canvas,
    alpha: true,
    antialias: true,
    powerPreference: "high-performance",
  });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));

  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(55, 1, 0.1, 200);
  camera.position.set(0, 0, 0);

  const materials = Array.from({ length: visibleCount }, () => createClothMaterial(THREE));
  const meshes = [];
  const planes = Array.from({ length: visibleCount }, (_, i) => ({
    index: i,
    z: ((DEPTH_RANGE / visibleCount) * i) % DEPTH_RANGE,
    imageIndex: i % totalImages,
    x: spatial[i].x,
    y: spatial[i].y,
  }));

  planes.forEach((plane, i) => {
    const { tex } = textures[plane.imageIndex];
    const mat = materials[i];
    mat.uniforms.map.value = tex;
    const aspect = tex.image ? tex.image.width / tex.image.height : 1;
    const geo = new THREE.PlaneGeometry(1, 1, 32, 32);
    const mesh = new THREE.Mesh(geo, mat);
    if (aspect > 1) mesh.scale.set(2 * aspect, 2, 1);
    else mesh.scale.set(2, 2 / aspect, 1);
    mesh.userData.planeIndex = i;
    mesh.userData.onHover = (v) => { mat.uniforms.isHovered.value = v ? 1 : 0; };
    scene.add(mesh);
    meshes.push(mesh);
  });

  let scrollVelocity = 0;
  let autoPlay = true;
  let lastInteraction = Date.now();
  let running = false;
  let frameId = 0;
  const raycaster = new THREE.Raycaster();
  const pointer = new THREE.Vector2();
  let hoveredMesh = null;

  const resize = () => {
    const rect = root.getBoundingClientRect();
    const w = Math.max(1, Math.floor(rect.width));
    const h = Math.max(1, Math.floor(rect.height));
    renderer.setSize(w, h, false);
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
  };

  const onWheel = (e) => {
    e.preventDefault();
    e.stopPropagation();
    scrollVelocity += e.deltaY * 0.01 * speed;
    autoPlay = false;
    lastInteraction = Date.now();
  };

  const onKey = (e) => {
    if (!root.matches(":hover") && document.activeElement !== document.body) return;
    if (e.key === "ArrowDown" || e.key === "ArrowRight") scrollVelocity += 2 * speed;
    else if (e.key === "ArrowUp" || e.key === "ArrowLeft") scrollVelocity -= 2 * speed;
    else return;
    autoPlay = false;
    lastInteraction = Date.now();
  };

  const onPointerMove = (e) => {
    const rect = canvas.getBoundingClientRect();
    pointer.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
    pointer.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;
    raycaster.setFromCamera(pointer, camera);
    const hits = raycaster.intersectObjects(meshes);
    const next = hits[0]?.object || null;
    if (hoveredMesh !== next) {
      hoveredMesh?.userData.onHover?.(false);
      hoveredMesh = next;
      hoveredMesh?.userData.onHover?.(true);
    }
  };

  let lastTime = 0;

  const tick = (time) => {
    if (!running) {
      frameId = 0;
      return;
    }

    const delta = Math.min(0.05, (time - lastTime) / 1000 || 0.016);
    lastTime = time;

    if (Date.now() - lastInteraction > 3000) autoPlay = true;
    if (autoPlay) scrollVelocity += 0.3 * delta;
    scrollVelocity *= 0.95;

    const half = DEPTH_RANGE / 2;
    const imageAdvance = visibleCount % totalImages || totalImages;

    materials.forEach((mat) => {
      mat.uniforms.time.value = time * 0.001;
      mat.uniforms.scrollForce.value = scrollVelocity;
    });

    planes.forEach((plane, i) => {
      let newZ = plane.z + scrollVelocity * delta * 10;
      let wrapsF = 0;
      let wrapsB = 0;

      if (newZ >= DEPTH_RANGE) {
        wrapsF = Math.floor(newZ / DEPTH_RANGE);
        newZ -= DEPTH_RANGE * wrapsF;
      } else if (newZ < 0) {
        wrapsB = Math.ceil(-newZ / DEPTH_RANGE);
        newZ += DEPTH_RANGE * wrapsB;
      }

      if (wrapsF > 0) plane.imageIndex = (plane.imageIndex + wrapsF * imageAdvance) % totalImages;
      if (wrapsB > 0) {
        plane.imageIndex = ((plane.imageIndex - wrapsB * imageAdvance) % totalImages + totalImages) % totalImages;
      }

      plane.z = ((newZ % DEPTH_RANGE) + DEPTH_RANGE) % DEPTH_RANGE;

      const t = plane.z / DEPTH_RANGE;
      materials[i].uniforms.opacity.value = calcOpacity(t, FADE);
      materials[i].uniforms.blurAmount.value = calcBlur(t, BLUR);

      const { tex } = textures[plane.imageIndex];
      if (materials[i].uniforms.map.value !== tex) {
        materials[i].uniforms.map.value = tex;
        const aspect = tex.image ? tex.image.width / tex.image.height : 1;
        if (aspect > 1) meshes[i].scale.set(2 * aspect, 2, 1);
        else meshes[i].scale.set(2, 2 / aspect, 1);
      }

      meshes[i].position.set(spatial[i].x, spatial[i].y, plane.z - half);
    });

    renderer.render(scene, camera);
    frameId = requestAnimationFrame(tick);
  };

  const setRunning = (next) => {
    if (next === running) return;
    running = next;
    if (running && !frameId) {
      lastTime = 0;
      frameId = requestAnimationFrame(tick);
    } else if (!running && frameId) {
      cancelAnimationFrame(frameId);
      frameId = 0;
    }
  };

  resize();
  root.classList.add("is-ready");

  root.addEventListener("wheel", onWheel, { passive: false });
  window.addEventListener("keydown", onKey);
  window.addEventListener("resize", resize);
  canvas.addEventListener("pointermove", onPointerMove, { passive: true });
  canvas.addEventListener("pointerleave", () => {
    hoveredMesh?.userData.onHover?.(false);
    hoveredMesh = null;
  });

  const visibilityObserver = new IntersectionObserver(
    ([entry]) => setRunning(entry.isIntersecting),
    { rootMargin: "80px", threshold: 0.05 }
  );
  visibilityObserver.observe(root);
  setRunning(true);
}
