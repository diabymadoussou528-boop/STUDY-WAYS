@extends('layouts.professor')

@section('title', 'Nouveau cours')

@section('content')
<x-admin-page-header kicker="Contenu pédagogique" title="Ajouter un cours" subtitle="Remplissez les détails et importez votre vidéo." />

@if($errors->any())
    <div class="flash-toast flash-toast--error" style="margin-bottom:16px;">
        <i class="fas fa-exclamation-circle"></i>
        {{ $errors->first() }}
    </div>
@endif

@if(session('error'))
    <div class="flash-toast flash-toast--error" style="margin-bottom:16px;">
        <i class="fas fa-exclamation-circle"></i>
        {{ session('error') }}
    </div>
@endif

<section class="widget-card glass-card reveal-up">
    <div class="widget-body" style="max-width: 800px; margin: 0 auto; padding: 24px;">
        <form method="POST" action="{{ $storeRoute ?? route('courses.store') }}" enctype="multipart/form-data" id="courseForm" style="display:flex; flex-direction:column; gap:20px;">
            @csrf

            <!-- Title -->
            <div class="form-group">
                <label class="form-label" style="font-weight:700; margin-bottom:6px; display:block;">Titre du cours</label>
                <input type="text" name="title" class="form-input" style="width:100%;" placeholder="Ex : Introduction au JavaScript moderne" required value="{{ old('title') }}">
            </div>

            <!-- Short Description -->
            <div class="form-group">
                <label class="form-label" style="font-weight:700; margin-bottom:6px; display:block;">Description courte</label>
                <input type="text" name="short_description" class="form-input" style="width:100%;" placeholder="Ex : Apprenez les bases de JS ES6+ en moins d'une heure." required value="{{ old('short_description') }}">
            </div>

            <!-- Description -->
            <div class="form-group">
                <label class="form-label" style="font-weight:700; margin-bottom:6px; display:block;">Description complète</label>
                <textarea name="description" class="form-input" rows="6" style="width:100%; resize:vertical;" placeholder="Présentez le programme complet, les chapitres..." required>{{ old('description') }}</textarea>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                <!-- Category -->
                <div class="form-group">
                    <label class="form-label" style="font-weight:700; margin-bottom:6px; display:block;">Catégorie</label>
                    <input type="text" name="category" class="form-input" style="width:100%;" placeholder="Ex : Programmation" required value="{{ old('category') }}">
                </div>

                <!-- Language -->
                <div class="form-group">
                    <label class="form-label" style="font-weight:700; margin-bottom:6px; display:block;">Langue</label>
                    <input type="text" name="language" class="form-input" style="width:100%;" placeholder="Ex : Français" required value="{{ old('language', 'Français') }}">
                </div>
            </div>

            <!-- Estimated Duration (Hours) -->
            <div class="form-group">
                <label class="form-label" style="font-weight:700; margin-bottom:6px; display:block;">Durée (Nombre d'heures)</label>
                <input type="number" name="duration_hours" class="form-input" style="width:100%;" min="0.1" step="0.1" placeholder="Ex : 2.5" required value="{{ old('duration_hours') }}">
            </div>

            <!-- Objectives -->
            <div class="form-group">
                <label class="form-label" style="font-weight:700; margin-bottom:6px; display:block;">Objectifs d'apprentissage (un par ligne)</label>
                <textarea name="objectives" class="form-input" rows="3" style="width:100%; resize:vertical;" placeholder="Maîtriser les bases de la syntaxe&#10;Comprendre les fonctions asynchrones..." required>{{ old('objectives') }}</textarea>
            </div>

            <!-- Unified Media Preview -->
            <div class="form-group">
                <label class="form-label" style="font-weight:700; margin-bottom:10px; display:block;">Aperçu du média du cours</label>
                <div class="glass-card" style="padding:18px; border-radius:var(--radius-md); border:1px solid var(--border); background:var(--bg-elevated); display:flex; flex-direction:column; gap:16px;">
                    <div id="courseMediaPreview" style="position:relative; aspect-ratio:16 / 9; width:100%; border-radius:var(--radius-md); overflow:hidden; border:1px solid var(--border); background:radial-gradient(circle at top, rgba(139,32,50,0.18), transparent 48%), linear-gradient(135deg, rgba(15,23,42,0.92), rgba(30,41,59,0.84)); box-shadow:0 16px 32px rgba(15,23,42,0.18);">
                        <img id="thumbnailPreview" src="" alt="Aperçu de la miniature du cours" style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover; display:none;">
                        <video id="coursePreviewVideo" playsinline preload="metadata" controls style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover; display:none; background:#000;"></video>
                        <div id="mediaPreviewPlaceholder" style="position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px; color:rgba(255,255,255,0.86); text-align:center; padding:24px; transition:opacity .2s ease;">
                            <div style="width:72px; height:72px; border-radius:50%; display:grid; place-items:center; border:1px solid rgba(255,255,255,0.18); background:rgba(255,255,255,0.08); backdrop-filter:blur(10px);">
                                <i class="fas fa-video" style="font-size:1.4rem;"></i>
                            </div>
                            <div>
                                <div style="font-size:1rem; font-weight:700; margin-bottom:4px;">Prévisualisation du cours</div>
                                <div style="font-size:0.85rem; color:rgba(255,255,255,0.72);">Ajoutez une image et une vidéo pour voir le rendu final.</div>
                            </div>
                        </div>
                        <button type="button" id="mediaPreviewPlayButton" aria-label="Lire l’aperçu vidéo" style="position:absolute; inset:50% auto auto 50%; transform:translate(-50%, -50%); width:78px; height:78px; border:none; border-radius:50%; display:flex; align-items:center; justify-content:center; background:rgba(15,23,42,0.58); color:#fff; cursor:pointer; box-shadow:0 10px 30px rgba(15,23,42,0.28); transition:transform .2s ease, background .2s ease, opacity .2s ease;">
                            <i class="fas fa-play" style="font-size:1.35rem; margin-left:4px;"></i>
                        </button>
                        <div id="mediaPreviewHint" style="position:absolute; left:16px; right:16px; bottom:16px; padding:10px 14px; border-radius:var(--radius-sm); background:rgba(15,23,42,0.72); color:#fff; font-size:0.82rem; opacity:0; pointer-events:none; transform:translateY(8px); transition:opacity .2s ease, transform .2s ease;"></div>
                    </div>

                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px;">
                        <div class="upload-dropzone" id="thumbnailDropzone" style="border: 2px dashed var(--border); border-radius: var(--radius-md); padding: 18px; text-align: center; cursor: pointer; background: var(--bg-elevated); transition: all 0.2s;">
                            <i class="fas fa-image" style="font-size: 1.5rem; color: var(--primary); margin-bottom: 8px;"></i>
                            <p style="font-size: 0.9rem; margin-bottom: 4px; font-weight: 700;">Changer l'image</p>
                            <span style="font-size: 0.75rem; color: var(--text-muted);">JPEG, PNG, GIF, WEBP · Max 2 Mo</span>
                            <input type="file" name="thumbnail" id="thumbnailInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;">
                        </div>

                        <div class="upload-dropzone" id="videoDropzone" style="border: 2px dashed var(--border); border-radius: var(--radius-md); padding: 18px; text-align: center; cursor: pointer; background: var(--bg-elevated); transition: all 0.2s;">
                            <i class="fas fa-video" style="font-size: 1.5rem; color: var(--primary); margin-bottom: 8px;"></i>
                            <p style="font-size: 0.9rem; margin-bottom: 4px; font-weight: 700;">Changer la vidéo</p>
                            <span style="font-size: 0.75rem; color: var(--text-muted);" id="videoLimitsLabel">MP4, WEBM, MOV, AVI · Max 1 Go</span>
                            <input type="file" name="video" id="videoInput" accept="video/mp4,video/webm,video/ogg,video/quicktime,video/x-msvideo" style="display:none;" required>
                        </div>
                    </div>
                    <div style="display:flex; flex-wrap:wrap; gap:10px 18px; align-items:center; font-size:0.82rem; color:var(--text-muted);">
                        <div id="thumbnailStatus" style="display:flex; align-items:center; gap:8px;">
                            <i class="fas fa-image" style="color:var(--primary);"></i>
                            <span>Aucune image sélectionnée</span>
                        </div>
                        <div id="videoNameDisplay" style="display:flex; align-items:center; gap:8px;">
                            <i class="fas fa-video" style="color:var(--primary);"></i>
                            <span>Aucune vidéo sélectionnée</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button & Loader -->
            <div style="margin-top:12px;">
                <button type="submit" class="btn btn-primary btn-block btn-glow" id="submitBtn" style="justify-content:center; gap:8px;">
                    <i class="fas fa-save"></i> Créer le cours
                </button>
            </div>
        </form>

        <!-- Loading Overlay during Upload -->
        <div id="uploadLoader" style="display:none; position:fixed; inset:0; background:rgba(255,255,255,0.92); z-index:9999; align-items:center; justify-content:center; flex-direction:column; gap:14px;">
            <div style="font-size:2rem; color:var(--primary);"><i class="fas fa-circle-notch fa-spin"></i></div>
            <div style="font-weight:700; color:var(--text);" id="uploadStatusTitle">Importation du cours en cours…</div>
            <div style="width:min(420px,80vw); height:10px; background:var(--bg-elevated); border-radius:999px; overflow:hidden; border:1px solid var(--border);">
                <div id="uploadProgressBar" style="height:100%; width:0%; background:linear-gradient(90deg,var(--primary),#c2415a); transition:width .15s ease;"></div>
            </div>
            <div style="font-size:0.9rem; font-weight:600; color:var(--primary);" id="uploadProgressPct">0%</div>
            <div style="font-size:0.8rem; color:var(--text-muted);" id="uploadStatusSub">Envoi des fichiers au serveur…</div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    function createMediaPreviewController(options) {
        const thumbnailInput = document.getElementById(options.thumbnailInputId);
        const videoInput = document.getElementById(options.videoInputId);
        const posterImage = document.getElementById(options.posterImageId);
        const videoElement = document.getElementById(options.videoElementId);
        const placeholder = document.getElementById(options.placeholderId);
        const playButton = document.getElementById(options.playButtonId);
        const hint = document.getElementById(options.hintId);
        const thumbnailStatus = document.getElementById(options.thumbnailStatusId);
        const videoStatus = document.getElementById(options.videoStatusId);

        let thumbnailObjectUrl = null;
        let videoObjectUrl = null;
        let hintTimer = null;
        let isPlaying = false;

        function revokeObjectUrl(url) {
            if (url) {
                URL.revokeObjectURL(url);
            }
        }

        function showHint(message) {
            hint.textContent = message;
            hint.style.opacity = '1';
            hint.style.transform = 'translateY(0)';
            clearTimeout(hintTimer);
            hintTimer = setTimeout(() => {
                hint.style.opacity = '0';
                hint.style.transform = 'translateY(8px)';
            }, 2200);
        }

        function setStatus(container, icon, text) {
            const span = container.querySelector('span');
            const iconEl = container.querySelector('i');
            if (iconEl) {
                iconEl.className = icon;
            }
            if (span) {
                span.textContent = text;
            }
        }

        function formatFileSize(bytes) {
            return (bytes / (1024 * 1024)).toFixed(2) + ' Mo';
        }

        function syncOverlay() {
            playButton.style.display = isPlaying ? 'none' : 'flex';
        }

        function syncPreviewSurface() {
            const posterSrc = posterImage.getAttribute('src');
            const hasPoster = Boolean(posterSrc);
            const hasVideo = Boolean(videoElement.getAttribute('src'));

            posterImage.style.display = !isPlaying && hasPoster ? 'block' : 'none';
            videoElement.style.display = isPlaying || (!hasPoster && hasVideo) ? 'block' : 'none';
            placeholder.style.display = !hasPoster && !hasVideo ? 'flex' : 'none';
            syncOverlay();
        }

        function stopPlayback(resetTime = false) {
            videoElement.pause();
            if (resetTime) {
                videoElement.currentTime = 0;
            }
            isPlaying = false;
            syncPreviewSurface();
        }

        let hasCustomThumbnail = false;

        function updateThumbnail(file, isAutoGenerated = false) {
            if (!file) return;

            if (!isAutoGenerated) {
                hasCustomThumbnail = true;
            }

            revokeObjectUrl(thumbnailObjectUrl);
            thumbnailObjectUrl = URL.createObjectURL(file);
            posterImage.src = thumbnailObjectUrl;
            setStatus(thumbnailStatus, 'fas fa-image', file.name + ' (' + formatFileSize(file.size) + ')' + (isAutoGenerated ? ' [Générée]' : ''));
            stopPlayback(true);
        }

        function generateThumbnailFromVideo(file) {
            if (hasCustomThumbnail) return;

            const tempVideo = document.createElement('video');
            tempVideo.preload = 'metadata';
            tempVideo.muted = true;
            tempVideo.playsInline = true;

            const tempUrl = URL.createObjectURL(file);
            tempVideo.src = tempUrl;

            tempVideo.addEventListener('loadedmetadata', () => {
                const seekTime = Math.min(1.0, tempVideo.duration / 2);
                tempVideo.currentTime = seekTime;
            });

            tempVideo.addEventListener('seeked', () => {
                try {
                    const canvas = document.createElement('canvas');
                    canvas.width = tempVideo.videoWidth || 640;
                    canvas.height = tempVideo.videoHeight || 360;
                    
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(tempVideo, 0, 0, canvas.width, canvas.height);
                    
                    canvas.toBlob((blob) => {
                        if (blob && !hasCustomThumbnail) {
                            const generatedFile = new File([blob], 'thumbnail.jpg', { type: 'image/jpeg' });
                            
                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(generatedFile);
                            thumbnailInput.files = dataTransfer.files;

                            updateThumbnail(generatedFile, true);
                        }
                        URL.revokeObjectURL(tempUrl);
                    }, 'image/jpeg', 0.85);
                } catch (e) {
                    console.error('Error generating thumbnail:', e);
                    URL.revokeObjectURL(tempUrl);
                }
            });

            tempVideo.addEventListener('error', () => {
                URL.revokeObjectURL(tempUrl);
            });
        }

        function updateVideo(file) {
            if (!file) return;

            revokeObjectUrl(videoObjectUrl);
            videoObjectUrl = URL.createObjectURL(file);
            videoElement.src = videoObjectUrl;
            videoElement.load();
            setStatus(videoStatus, 'fas fa-video', file.name + ' (' + formatFileSize(file.size) + ')');
            stopPlayback(true);

            generateThumbnailFromVideo(file);
        }

        playButton.addEventListener('mouseenter', () => {
            playButton.style.transform = 'translate(-50%, -50%) scale(1.06)';
            playButton.style.background = 'rgba(15,23,42,0.74)';
        });

        playButton.addEventListener('mouseleave', () => {
            playButton.style.transform = 'translate(-50%, -50%) scale(1)';
            playButton.style.background = 'rgba(15,23,42,0.58)';
        });

        playButton.addEventListener('click', async () => {
            if (!videoElement.getAttribute('src')) {
                showHint('Veuillez d’abord ajouter une vidéo.');
                return;
            }

            isPlaying = true;
            syncPreviewSurface();

            try {
                videoElement.muted = false;
                await videoElement.play();
            } catch (error) {
                isPlaying = false;
                syncPreviewSurface();
                showHint('Impossible de lancer la lecture automatique. Cliquez sur lecture dans le lecteur.');
            }
        });

        videoElement.addEventListener('pause', () => {
            if (!videoElement.ended) {
                isPlaying = false;
                syncPreviewSurface();
            }
        });

        videoElement.addEventListener('ended', () => {
            isPlaying = false;
            syncPreviewSurface();
        });

        videoElement.addEventListener('play', () => {
            isPlaying = true;
            syncPreviewSurface();
        });

        window.addEventListener('beforeunload', () => {
            revokeObjectUrl(thumbnailObjectUrl);
            revokeObjectUrl(videoObjectUrl);
        });

        syncPreviewSurface();

        return {
            handleThumbnailFile: updateThumbnail,
            handleVideoFile: updateVideo,
        };
    }

    // Helper setup for Drag & Drop Dropzones
    function setupDropzone(dropzoneId, inputId, onFileSelect) {
        const dropzone = document.getElementById(dropzoneId);
        const input = document.getElementById(inputId);

        dropzone.addEventListener('click', () => input.click());

        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.style.borderColor = 'var(--primary)';
            dropzone.style.background = 'rgba(139,32,50,0.02)';
        });

        dropzone.addEventListener('dragleave', () => {
            dropzone.style.borderColor = 'var(--border)';
            dropzone.style.background = 'var(--bg-elevated)';
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.style.borderColor = 'var(--border)';
            dropzone.style.background = 'var(--bg-elevated)';

            if (e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                handleFileSelect(input.files[0]);
            }
        });

        input.addEventListener('change', () => {
            if (input.files.length) {
                handleFileSelect(input.files[0]);
            }
        });

        function handleFileSelect(file) {
            if (!file) return;
            onFileSelect(file);
        }
    }

    const mediaPreview = createMediaPreviewController({
        thumbnailInputId: 'thumbnailInput',
        videoInputId: 'videoInput',
        posterImageId: 'thumbnailPreview',
        videoElementId: 'coursePreviewVideo',
        placeholderId: 'mediaPreviewPlaceholder',
        playButtonId: 'mediaPreviewPlayButton',
        hintId: 'mediaPreviewHint',
        thumbnailStatusId: 'thumbnailStatus',
        videoStatusId: 'videoNameDisplay',
    });

    setupDropzone('thumbnailDropzone', 'thumbnailInput', mediaPreview.handleThumbnailFile);
    setupDropzone('videoDropzone', 'videoInput', mediaPreview.handleVideoFile);

    // Submit via XHR so we can show a real upload percentage and keep the UI responsive.
    const form = document.getElementById('courseForm');
    const loader = document.getElementById('uploadLoader');
    const bar = document.getElementById('uploadProgressBar');
    const pctEl = document.getElementById('uploadProgressPct');
    const sub = document.getElementById('uploadStatusSub');
    const title = document.getElementById('uploadStatusTitle');

    function showFlashError(msg) {
        document.querySelector('.flash-toast--error')?.remove();
        const div = document.createElement('div');
        div.className = 'flash-toast flash-toast--error';
        div.style.marginBottom = '16px';
        div.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + msg;
        form.parentElement.insertBefore(div, form);
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();

        if (! form.checkValidity()) {
            form.reportValidity();
            return;
        }

        loader.style.display = 'flex';
        bar.style.width = '0%';
        pctEl.textContent = '0%';
        sub.textContent = 'Envoi des fichiers au serveur…';
        title.textContent = 'Importation du cours en cours…';

        const fd = new FormData(form);
        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.action, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.upload.onprogress = (ev) => {
            if (ev.lengthComputable) {
                const pct = Math.round((ev.loaded / ev.total) * 100);
                bar.style.width = pct + '%';
                pctEl.textContent = pct + '%';
                sub.textContent = pct < 100
                    ? 'Envoi des fichiers… ' + pct + '%'
                    : 'Traitement de la vidéo par le serveur…';
            }
        };

        xhr.onload = () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                title.textContent = 'Finalisation…';
                const location = xhr.getResponseHeader('Location');
                window.location = location || window.location.href;
                return;
            }
            loader.style.display = 'none';
            let msg = 'Une erreur est survenue lors de l’importation.';
            try {
                const j = JSON.parse(xhr.responseText);
                if (j.message) msg = j.message;
                else if (j.errors) msg = Object.values(j.errors).flat().join(' ');
            } catch (err) { /* ignore */ }
            showFlashError(msg);
        };

        xhr.onerror = () => {
            loader.style.display = 'none';
            showFlashError('Impossible de contacter le serveur. Vérifiez votre connexion.');
        };

        xhr.ontimeout = () => {
            loader.style.display = 'none';
            showFlashError('Délai d’attente dépassé. Réessayez ou utilisez un fichier plus léger.');
        };

        xhr.send(fd);
    });
</script>
@endsection
