var PRE_PAUSE_THRESHOLD = 800;
var POLL_INTERVAL = 150;

var state = {
    isPlaying: false,
    currentTrack: 0,
    trackCount: 0,
    isCustomAudioPlaying: false,
    lastTrackId: null,
    sdkReady: false,
    sdkDeviceId: null,
    player: null,
    spotifyToken: null,
    progressTimer: null,
    lastSdkPosition: 0,
    lastSdkPositionTime: 0,
    trackTotalMs: 0,
    audioPending: false,
    audioScheduled: false,
    monitoringFinTrack: false,
    monitorTrackId: null,
    pollPauseTimer: null,
    rePauseTimer: null,
    realTrackId: null,
    prevTrackId: null,
};

var customAudio = document.createElement('audio');
customAudio.id = 'customAudioPlayer';
customAudio.style.display = 'none';
document.body.appendChild(customAudio);

var eventLog = [];

function logEvent(msg) {
    var timestamp = new Date().toISOString().substr(11, 12);
    eventLog.push(timestamp + ' ' + msg);
    if (eventLog.length > 50) eventLog.shift();
    actualizarLogPanel();
}

window.onSpotifyWebPlaybackSDKReady = function() {
    fetch('index.php?accion=player_token')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.error || !data.access_token) {
                document.getElementById('deviceName').textContent = 'Error: ' + (data.error || 'sin token');
                return;
            }
            state.spotifyToken = data.access_token;
            inicializarSDK(data.access_token);
        });
};

function inicializarSDK(token) {
    state.player = new Spotify.Player({
        name: 'SpotyAudio Web Player',
        getOAuthToken: function(cb) { cb(token); },
        volume: 0.8,
    });

    state.player.addListener('ready', function(dev) {
        state.sdkDeviceId = dev.device_id;
        state.sdkReady = true;
        actualizarIndicadorDispositivo('SpotyAudio Web Player', true);
        document.getElementById('btnCambiarDispositivo').classList.remove('hidden');
    });

    state.player.addListener('not_ready', function() {
        state.sdkReady = false;
        detenerTimerProgreso();
        actualizarIndicadorDispositivo('Desconectado', false);
    });

    state.player.addListener('player_state_changed', function(sdkState) {
        if (!sdkState) return;
        if (state.audioPending) {
            if (sdkState.paused) {
                audioPendingPauseConfirmado(sdkState);
            }
            return;
        }
        if (state.isCustomAudioPlaying) {
            if (!sdkState.paused && sdkState.track_window && sdkState.track_window.current_track) {
                logEvent('[RE-PAUSE] Spotify no pausado, re-pausando');
                state.player.pause();
            }
            actualizarRealTrack(sdkState);
            actualizarDebug(null, sdkState);
            return;
        }
        procesarEstadoSDK(sdkState);
    });

    state.player.addListener('initialization_error', function(e) {
        actualizarIndicadorDispositivo('Error: ' + e.message, false);
        logEvent('[ERROR] init: ' + e.message);
    });
    state.player.addListener('authentication_error', function(e) {
        actualizarIndicadorDispositivo('Error auth: ' + e.message, false);
        logEvent('[ERROR] auth: ' + e.message);
    });
    state.player.addListener('account_error', function(e) {
        actualizarIndicadorDispositivo('Error: ' + e.message, false);
        logEvent('[ERROR] account: ' + e.message);
    });

    state.player.connect();
}

function procesarEstadoSDK(sdkState) {
    if (!sdkState || !sdkState.track_window || !sdkState.track_window.current_track) return;

    var track = sdkState.track_window.current_track;
    state.isPlaying = !sdkState.paused;

    var sdkPos = sdkState.position || 0;
    state.lastSdkPosition = sdkPos;
    state.lastSdkPositionTime = Date.now();
    state.trackTotalMs = track.duration_ms;
    state.realTrackId = track.id;

    if (track.id !== state.lastTrackId) {
        var oldId = state.lastTrackId ? state.lastTrackId.substr(0, 16) : 'null';
        var newId = track.id.substr(0, 16);

        if (state.audioScheduled && state.monitoringFinTrack) {
            logEvent('[TRACK END] old=' + oldId + ' name=' + (state.prevTrackName || ''));
            logEvent('[AUDIO INSERTION POINT] track cambio antes de pre-pausa, deteniendo inmediatamente');
            state.monitoringFinTrack = false;
            state.lastTrackId = track.id;
            pausarYReproducirAudio();
            return;
        }

        logEvent('[TRACK START] id=' + newId + ' name=' + (track.name || ''));
        var antes = state.trackCount;
        state.prevTrackName = track.name;
        state.lastTrackId = track.id;
        incrementarContador();
        logEvent('[COUNTER BEFORE] ' + antes);
        logEvent('[COUNTER AFTER] ' + state.trackCount);
        logEvent('[TRACK REAL] id=' + newId);
    }

    actualizarCancionActual({
        id: track.id,
        name: track.name,
        artists: track.artists,
        album: track.album,
        duration_ms: track.duration_ms,
    });

    mostrarTiempoSDK(sdkPos);
    sincronizarBarraProgreso(sdkPos);
    actualizarIconoPlayPause();
    actualizarDebug(track, sdkState);
}

function incrementarContador() {
    state.trackCount++;
    state.currentTrack++;
    document.getElementById('cancionActualNum').textContent = state.currentTrack;
    actualizarContadores();

    if (state.trackCount >= CONFIG.intervalo && !state.isCustomAudioPlaying && !state.audioPending) {
        state.audioScheduled = true;
        state.monitoringFinTrack = true;
        state.monitorTrackId = state.lastTrackId;
        logEvent('[AUDIO SCHEDULED] trackCount=' + state.trackCount + ' >= ' + CONFIG.intervalo + ', monitorizando fin de track ' + (state.lastTrackId || '').substr(0, 16));
    } else {
        logEvent('[AUDIO] NO trigger: trackCount=' + state.trackCount + ' < ' + CONFIG.intervalo + ' or busy');
    }
}

function pausarYReproducirAudio() {
    if (state.isCustomAudioPlaying || state.audioPending) return;
    if (state.trackCount < CONFIG.intervalo && !state.audioScheduled) return;

    logEvent('[SPOTIFY PAUSE REQUEST]');

    state.audioPending = true;
    detenerTimerProgreso();
    detenerPollPause();

    state.player.pause().catch(function(e) {
        logEvent('[SPOTIFY PAUSE] ERROR: ' + (e.message || ''));
    });

    iniciarPollPause();
}

function iniciarPollPause() {
    detenerPollPause();
    state.pollPauseTimer = setInterval(function() {
        if (!state.audioPending) {
            detenerPollPause();
            return;
        }
        if (state.player) {
            state.player.getCurrentState().then(function(ss) {
                if (ss && ss.paused) {
                    audioPendingPauseConfirmado(ss);
                }
            }).catch(function() {});
        }
    }, POLL_INTERVAL);

    setTimeout(function() {
        if (state.audioPending) {
            logEvent('[SPOTIFY PAUSE] TIMEOUT 4s, forzando audio');
            state.audioPending = false;
            detenerPollPause();
            iniciarAudioPersonalizado();
        }
    }, 4000);
}

function detenerPollPause() {
    if (state.pollPauseTimer) {
        clearInterval(state.pollPauseTimer);
        state.pollPauseTimer = null;
    }
}

function audioPendingPauseConfirmado(sdkState) {
    logEvent('[SPOTIFY PAUSE CONFIRMED]');
    state.audioPending = false;
    detenerPollPause();
    state.lastSdkPosition = sdkState.position || 0;
    state.lastSdkPositionTime = Date.now();
    iniciarAudioPersonalizado();
}

function iniciarAudioPersonalizado() {
    logEvent('[AUDIO START]');
    state.audioScheduled = false;
    state.monitoringFinTrack = false;
    state.monitorTrackId = null;
    state.isCustomAudioPlaying = true;
    state.lastSdkPosition = 0;
    state.lastSdkPositionTime = Date.now();

    document.getElementById('customAudioOverlay').classList.remove('hidden');
    document.getElementById('customAudioName').textContent = document.querySelector('.text-xl.font-bold')?.textContent || 'Audio';
    document.getElementById('currentTime').textContent = msToTime(0);

    iniciarRePauseTimer();

    customAudio.src = CONFIG.audioUrl;
    customAudio.currentTime = 0;
    customAudio.play().then(function() {
        logEvent('[AUDIO] reproduciendo: ' + CONFIG.audioUrl);
    }).catch(function(e) {
        logEvent('[AUDIO] ERROR play: ' + e.message);
    });
}

function iniciarRePauseTimer() {
    detenerRePauseTimer();
    state.rePauseTimer = setInterval(function() {
        if (!state.isCustomAudioPlaying) {
            detenerRePauseTimer();
            return;
        }
        if (state.player) {
            state.player.getCurrentState().then(function(sdkState) {
                if (sdkState && !sdkState.paused) {
                    logEvent('[RE-PAUSE] detectado no-pausado');
                    state.player.pause();
                }
            }).catch(function() {});
        }
    }, 1500);
}

function detenerRePauseTimer() {
    if (state.rePauseTimer) {
        clearInterval(state.rePauseTimer);
        state.rePauseTimer = null;
    }
}

customAudio.ontimeupdate = function() {
    var pct = (customAudio.currentTime / customAudio.duration) * 100;
    document.getElementById('customAudioProgress').style.width = (isNaN(pct) ? 0 : pct) + '%';
};

customAudio.onended = function() {
    logEvent('[AUDIO END]');
    document.getElementById('customAudioOverlay').classList.add('hidden');
    document.getElementById('customAudioProgress').style.width = '0%';
    detenerRePauseTimer();
    state.isCustomAudioPlaying = false;
    state.trackCount = 0;
    actualizarContadores();

    setTimeout(function() {
        logEvent('[SPOTIFY RESUME REQUEST] saltando al siguiente track');
        if (state.player) {
            state.player.nextTrack().then(function() {
                state.isPlaying = true;
                state.lastSdkPositionTime = Date.now();
                actualizarIconoPlayPause();
                iniciarTimerProgreso();
                logEvent('[SPOTIFY RESUME CONFIRMED] nextTrack()');
            }).catch(function() {
                logEvent('[SPOTIFY] nextTrack fallo, usando REST play');
                reanudarSpotifyREST();
            });
        } else {
            reanudarSpotifyREST();
        }
    }, 800);
};

function reanudarSpotifyREST() {
    fetch('index.php?accion=player_resume', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'config_id=' + CONFIG.configId + '&csrf_token=' + CONFIG.csrfToken
    })
    .then(function(r) { return r.json(); })
    .then(function(resp) {
        if (!resp.error) {
            state.isPlaying = true;
            actualizarIconoPlayPause();
            iniciarTimerProgreso();
            logEvent('[SPOTIFY RESUME CONFIRMED] REST');
        } else {
            logEvent('[SPOTIFY] ERROR REST: ' + resp.error);
        }
    })
    .catch(function() {
        logEvent('[SPOTIFY] ERROR REST: red');
    });
}

function iniciarReproduccion() {
    if (!state.sdkReady || !state.sdkDeviceId) {
        mostrarAdvertencia('Dispositivo', 'El reproductor web aún no está listo.');
        return;
    }

    state.isPlaying = true;
    state.currentTrack = 0;
    state.trackCount = 0;
    state.lastTrackId = null;
    state.lastSdkPosition = 0;
    state.lastSdkPositionTime = 0;
    state.trackTotalMs = 0;
    state.audioPending = false;
    state.audioScheduled = false;
    state.monitoringFinTrack = false;
    state.monitorTrackId = null;
    state.isCustomAudioPlaying = false;
    state.realTrackId = null;
    state.prevTrackName = null;
    eventLog = [];

    document.getElementById('playerEmptyState').classList.add('hidden');
    document.getElementById('playerContent').classList.remove('hidden');
    actualizarInterfaz();
    iniciarTimerProgreso();

    logEvent('[PLAY] inicio, trackCount=0');

    fetch('index.php?accion=player_play', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'config_id=' + CONFIG.configId + '&offset=0&csrf_token=' + CONFIG.csrfToken + '&device_id=' + state.sdkDeviceId
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.error) {
            mostrarAdvertencia('Error', data.error);
            return;
        }
        mostrarExito('Reproduciendo', 'Playlist iniciada');
    })
    .catch(function() {
        mostrarError('Error', 'No se pudo iniciar');
    });
}

function iniciarTimerProgreso() {
    detenerTimerProgreso();
    state.progressTimer = setInterval(function() {
        if (state.isCustomAudioPlaying || state.audioPending) return;

        if (state.monitoringFinTrack && state.lastSdkPositionTime > 0 && state.trackTotalMs > 0) {
            var elapsed = Date.now() - state.lastSdkPositionTime;
            var interpolated = state.lastSdkPosition + elapsed;
            var remaining = state.trackTotalMs - interpolated;

            if (remaining <= PRE_PAUSE_THRESHOLD && remaining > -2000 && state.lastTrackId === state.monitorTrackId) {
                logEvent('[AUDIO INSERTION POINT] remaining=' + Math.round(remaining) + 'ms, pre-pausando');
                state.monitoringFinTrack = false;
                pausarYReproducirAudio();
                return;
            }
        }

        if (state.isPlaying && state.lastSdkPositionTime > 0 && state.trackTotalMs > 0) {
            var elapsed = Date.now() - state.lastSdkPositionTime;
            var interpolated = state.lastSdkPosition + elapsed;
            if (interpolated > state.trackTotalMs) interpolated = state.trackTotalMs;
            if (interpolated < 0) interpolated = 0;
            mostrarTiempoInterpolado(interpolated);
            var pct = (interpolated / state.trackTotalMs) * 100;
            document.getElementById('progressBar').style.width = Math.min(pct, 100) + '%';
        }
    }, 250);
}

function detenerTimerProgreso() {
    if (state.progressTimer) {
        clearInterval(state.progressTimer);
        state.progressTimer = null;
    }
}

function mostrarTiempoSDK(ms) {
    document.getElementById('currentTime').textContent = msToTime(ms);
}

function mostrarTiempoInterpolado(ms) {
    document.getElementById('currentTime').textContent = msToTime(ms);
}

function sincronizarBarraProgreso(pos) {
    state.lastSdkPositionTime = Date.now();
    var pct = state.trackTotalMs > 0 ? (pos / state.trackTotalMs) * 100 : 0;
    document.getElementById('progressBar').style.width = Math.min(pct, 100) + '%';
}

function actualizarRealTrack(sdkState) {
    if (!sdkState || !sdkState.track_window || !sdkState.track_window.current_track) return;
    state.realTrackId = sdkState.track_window.current_track.id;
}

function actualizarCancionActual(item) {
    var nombre = document.getElementById('currentTrackName');
    var artista = document.getElementById('currentTrackArtist');
    var img = document.getElementById('currentTrackImage');

    if (nombre.textContent !== item.name) {
        nombre.textContent = item.name;
        artista.textContent = item.artists.map(function(a) { return a.name; }).join(', ');
        img.src = item.album.images[0]?.url || '';
        resaltarTrackEnLista(item.id);
    }

    document.getElementById('totalTime').textContent = msToTime(item.duration_ms || 0);
}

function resaltarTrackEnLista(trackId) {
    logEvent('[TRACK UI] resaltando ' + (trackId ? trackId.substr(0, 16) : 'null'));
    document.querySelectorAll('.track-item').forEach(function(el) {
        el.classList.remove('bg-spotify/10', 'text-spotify');
    });
    if (!trackId) return;
    document.querySelectorAll('.track-item').forEach(function(el) {
        if (el.dataset.trackId === trackId) {
            el.classList.add('bg-spotify/10', 'text-spotify');
        }
    });
}

function actualizarContadores() {
    var restantes = CONFIG.intervalo - state.trackCount;
    document.getElementById('cancionesRestantes').textContent = Math.max(0, restantes);
    document.getElementById('nextInsertionCount').textContent = Math.max(0, restantes);
}

function actualizarIconoPlayPause() {
    document.getElementById('playPauseIcon').className = state.isPlaying ? 'fas fa-pause text-2xl' : 'fas fa-play text-2xl';
}

function actualizarIndicadorDispositivo(nombre, activo) {
    document.getElementById('deviceName').textContent = nombre;
    document.getElementById('deviceIndicator').className = 'w-4 h-4 rounded-full ' + (activo ? 'bg-green-500' : 'bg-gray-400');
}

function actualizarInterfaz() {
    var el = document.getElementById('cancionesRestantes');
    if (el) el.textContent = CONFIG.intervalo;
    el = document.getElementById('nextInsertionCount');
    if (el) el.textContent = CONFIG.intervalo;
    el = document.getElementById('cancionActualNum');
    if (el) el.textContent = '0';
}

function togglePlayPause() {
    if (state.isCustomAudioPlaying) return;
    if (state.isPlaying) {
        if (state.player) state.player.pause();
        else fetch('index.php?accion=player_pause');
        state.isPlaying = false;
        detenerTimerProgreso();
        actualizarIconoPlayPause();
        logEvent('[TOGGLE] pause manual');
    } else {
        if (state.player) state.player.resume();
        else {
            fetch('index.php?accion=player_resume', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'config_id=' + CONFIG.configId + '&csrf_token=' + CONFIG.csrfToken
            });
        }
        state.isPlaying = true;
        state.lastSdkPositionTime = Date.now();
        iniciarTimerProgreso();
        actualizarIconoPlayPause();
        logEvent('[TOGGLE] resume manual');
    }
}

function siguienteCancion() {
    if (state.isCustomAudioPlaying || state.audioPending) {
        logEvent('[SKIP] bloqueado durante audio activo');
        return;
    }
    if (state.audioScheduled) {
        state.audioScheduled = false;
        state.monitoringFinTrack = false;
        state.monitorTrackId = null;
        state.trackCount = 0;
        actualizarContadores();
        logEvent('[SKIP] audio cancelado por skip manual, trackCount reset');
    }
    if (state.player) { state.player.nextTrack(); logEvent('[SKIP] nextTrack()'); }
    else { fetch('index.php?accion=player_next'); logEvent('[SKIP] REST next'); }
}

function anteriorCancion() {
    if (state.isCustomAudioPlaying || state.audioPending) {
        logEvent('[SKIP] bloqueado durante audio activo');
        return;
    }
    if (state.audioScheduled) {
        state.audioScheduled = false;
        state.monitoringFinTrack = false;
        state.monitorTrackId = null;
        state.trackCount = 0;
        actualizarContadores();
        logEvent('[SKIP] audio cancelado por skip manual, trackCount reset');
    }
    if (state.player) { state.player.previousTrack(); logEvent('[SKIP] previousTrack()'); }
}

function mostrarSelectorDispositivos() {
    var lista = document.getElementById('deviceList');
    if (!lista.classList.contains('hidden')) { lista.classList.add('hidden'); return; }
    fetch('index.php?accion=player_devices')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var devices = data.dispositivos || [];
            var html = '';
            if (state.sdkDeviceId) {
                html += '<button onclick="seleccionarDispositivo(\'' + state.sdkDeviceId + '\')" class="flex items-center gap-2 text-sm text-spotify hover:text-white transition-colors w-full text-left px-2 py-1.5 rounded hover:bg-gray-100 dark:hover:bg-darkBorder"><i class="fas fa-laptop w-4"></i><span class="truncate font-medium">SpotyAudio Web Player</span><i class="fas fa-check-circle ml-auto text-xs"></i></button>';
            }
            devices.forEach(function(dev) {
                if (dev.id === state.sdkDeviceId) return;
                var icon = dev.type === 'Computer' ? 'fa-laptop' : (dev.type === 'Smartphone' ? 'fa-mobile-alt' : 'fa-speaker');
                html += '<button onclick="seleccionarDispositivo(\'' + dev.id + '\')" class="flex items-center gap-2 text-sm text-gray-400 hover:text-white transition-colors w-full text-left px-2 py-1.5 rounded hover:bg-gray-100 dark:hover:bg-darkBorder"><i class="fas ' + icon + ' w-4"></i><span class="truncate">' + (dev.name || 'Desconocido') + '</span></button>';
            });
            lista.innerHTML = html || '<p class="text-xs text-gray-400">No hay dispositivos disponibles</p>';
            lista.classList.remove('hidden');
        })
        .catch(function() {
            lista.innerHTML = '<p class="text-xs text-gray-400">Error al cargar</p>';
            lista.classList.remove('hidden');
        });
}

function seleccionarDispositivo(deviceId) {
    if (deviceId === state.sdkDeviceId) { document.getElementById('deviceList').classList.add('hidden'); return; }
    fetch('index.php?accion=player_transfer', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'device_id=' + encodeURIComponent(deviceId) + '&csrf_token=' + CONFIG.csrfToken
    })
    .then(function(r) { return r.json(); })
    .then(function() {
        actualizarIndicadorDispositivo('Dispositivo externo', true);
        mostrarExito('Dispositivo', 'Reproducción transferida');
        document.getElementById('deviceList').classList.add('hidden');
    });
}

function actualizarDebug(track, sdkState) {
    var el = document.getElementById('debugInfo');
    if (!el) return;
    var paused = sdkState ? sdkState.paused : '?';
    var pos = sdkState ? sdkState.position : 0;
    var dur = track ? track.duration_ms : 0;
    el.innerHTML =
        '<div class="text-xs font-mono text-gray-400">' +
        '<span class="text-spotify font-semibold">DEBUG</span>' +
        '<br>SDK: <span class="text-green-400">OK</span> Device: ' + (state.sdkDeviceId ? state.sdkDeviceId.substring(0, 12) + '...' : '--') +
        '<br>Track: ' + (track ? track.id.substring(0, 16) + '...' : '--') +
        '<br>Pos: ' + msToTime(pos) + ' / ' + msToTime(dur) +
        '<br><span class="text-yellow-400">#' + state.currentTrack + ' C:' + state.trackCount + '/' + CONFIG.intervalo + '</span>' +
        '<br>Play:' + (state.isPlaying ? 'YES' : 'NO') + ' Paused:' + paused +
        ' Audio:' + (state.isCustomAudioPlaying ? 'ACT' : 'no') +
        ' Pend:' + (state.audioPending ? 'YES' : 'no') +
        ' Sch:' + (state.audioScheduled ? 'YES' : 'no') +
        ' Mon:' + (state.monitoringFinTrack ? 'YES' : 'no') +
        '<br>Interpol: ' + msToTime(state.lastSdkPosition + (state.lastSdkPositionTime > 0 ? (Date.now() - state.lastSdkPositionTime) : 0)) +
        '</div>';
    actualizarLogPanel();
}

function actualizarLogPanel() {
    var el = document.getElementById('eventLog');
    if (!el) return;
    el.innerHTML = eventLog.slice(-15).map(function(l) {
        var color = 'text-gray-400';
        if (l.indexOf('[ERROR]') >= 0) color = 'text-red-400';
        else if (l.indexOf('[AUDIO START]') >= 0 || l.indexOf('[AUDIO SCHEDULED]') >= 0) color = 'text-purple-400 font-semibold';
        else if (l.indexOf('[AUDIO INSERTION POINT]') >= 0) color = 'text-purple-400 font-bold';
        else if (l.indexOf('[AUDIO]') >= 0 && l.indexOf('ERROR') < 0) color = 'text-purple-400';
        else if (l.indexOf('[AUDIO END]') >= 0) color = 'text-purple-400';
        else if (l.indexOf('[SPOTIFY PAUSE CONFIRMED]') >= 0 || l.indexOf('[SPOTIFY RESUME CONFIRMED]') >= 0) color = 'text-green-400 font-semibold';
        else if (l.indexOf('[SPOTIFY]') >= 0) color = 'text-green-400';
        else if (l.indexOf('[COUNTER') >= 0) color = 'text-yellow-400';
        else if (l.indexOf('[TRACK START]') >= 0) color = 'text-blue-400 font-semibold';
        else if (l.indexOf('[TRACK END]') >= 0) color = 'text-orange-400 font-semibold';
        else if (l.indexOf('[TRACK') >= 0) color = 'text-cyan-400';
        else if (l.indexOf('[PLAY]') >= 0) color = 'text-green-400';
        else if (l.indexOf('[SKIP]') >= 0) color = 'text-orange-400';
        else if (l.indexOf('[TOGGLE]') >= 0) color = 'text-gray-300';
        else if (l.indexOf('[RE-PAUSE]') >= 0) color = 'text-red-400';
        return '<div class="' + color + '">' + l + '</div>';
    }).join('');
}

function msToTime(ms) {
    if (!ms && ms !== 0) return '0:00';
    var s = Math.floor(ms / 1000);
    var m = Math.floor(s / 60);
    var sec = s % 60;
    return m + ':' + (sec < 10 ? '0' : '') + sec;
}
