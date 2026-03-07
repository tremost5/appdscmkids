<?php
$pwaPushEnabled = (bool) config('pwa_push.enabled', true);
$pwaPushPublicKey = trim((string) config('pwa_push.public_key', ''));
?>
<?php if (session('isLoggedIn') && $pwaPushEnabled && $pwaPushPublicKey !== ''): ?>
<style>
.pwa-push-banner{position:fixed;right:16px;bottom:88px;z-index:1080;max-width:340px;background:#0f172a;color:#e2e8f0;border-radius:16px;padding:14px 16px;box-shadow:0 16px 44px rgba(15,23,42,.28);border:1px solid rgba(148,163,184,.24)}
.pwa-push-banner__title{font-weight:700;font-size:15px;margin-bottom:4px}
.pwa-push-banner__text{font-size:13px;line-height:1.45;color:#cbd5e1;margin-bottom:10px}
.pwa-push-banner__actions{display:flex;gap:8px;justify-content:flex-end}
.pwa-push-banner__btn{border:0;border-radius:10px;padding:8px 12px;font-size:12px;font-weight:700}
.pwa-push-banner__btn--ghost{background:#1e293b;color:#cbd5e1}
.pwa-push-banner__btn--primary{background:#22c55e;color:#052e16}
@media (max-width:768px){.pwa-push-banner{left:12px;right:12px;bottom:86px;max-width:none}}
</style>
<div id="pwaPushBanner" class="pwa-push-banner" style="display:none">
  <div class="pwa-push-banner__title">Aktifkan Notifikasi Materi</div>
  <div class="pwa-push-banner__text">Izinkan notifikasi agar HP ini menerima info setiap admin mengunggah materi baru.</div>
  <div class="pwa-push-banner__actions">
    <button type="button" id="pwaPushDismiss" class="pwa-push-banner__btn pwa-push-banner__btn--ghost">Nanti</button>
    <button type="button" id="pwaPushEnable" class="pwa-push-banner__btn pwa-push-banner__btn--primary">Aktifkan</button>
  </div>
</div>
<script>
(() => {
  if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) return;

  const publicKey = <?= json_encode($pwaPushPublicKey) ?>;
  const subscribeUrl = <?= json_encode(base_url('pwa/push/subscribe')) ?>;
  const unsubscribeUrl = <?= json_encode(base_url('pwa/push/unsubscribe')) ?>;
  const csrfToken = <?= json_encode(csrf_token()) ?>;
  const banner = document.getElementById('pwaPushBanner');
  const dismissBtn = document.getElementById('pwaPushDismiss');
  const enableBtn = document.getElementById('pwaPushEnable');
  const dismissKey = 'dscmkids-pwa-push-dismissed-v1';

  function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
  }

  async function postJson(url, payload) {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken,
      },
      credentials: 'same-origin',
      body: JSON.stringify(payload),
    });
    if (!response.ok) {
      throw new Error('HTTP ' + response.status);
    }
    return response.json();
  }

  async function syncSubscription() {
    const registration = await navigator.serviceWorker.ready;
    let subscription = await registration.pushManager.getSubscription();
    if (!subscription) {
      subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(publicKey),
      });
    }

    await postJson(subscribeUrl, { subscription: subscription.toJSON() });
    localStorage.removeItem(dismissKey);
    if (banner) banner.style.display = 'none';
  }

  async function detachSubscription() {
    const registration = await navigator.serviceWorker.ready;
    const subscription = await registration.pushManager.getSubscription();
    if (!subscription) return;
    await postJson(unsubscribeUrl, { endpoint: subscription.endpoint });
  }

  async function enableNotifications() {
    const permission = await Notification.requestPermission();
    if (permission === 'granted') {
      await syncSubscription();
      return;
    }
    if (permission === 'denied') {
      localStorage.setItem(dismissKey, '1');
    }
  }

  async function boot() {
    if (Notification.permission === 'granted') {
      try {
        await syncSubscription();
      } catch (error) {
        console.error('PWA push sync gagal', error);
      }
      return;
    }

    if (Notification.permission === 'denied') {
      try {
        await detachSubscription();
      } catch (error) {
        console.error('PWA push unsubscribe gagal', error);
      }
      return;
    }

    if (!banner || localStorage.getItem(dismissKey) === '1') return;
    banner.style.display = 'block';
  }

  enableBtn?.addEventListener('click', async () => {
    enableBtn.disabled = true;
    try {
      await enableNotifications();
    } catch (error) {
      console.error('PWA push activation gagal', error);
    } finally {
      enableBtn.disabled = false;
    }
  });

  dismissBtn?.addEventListener('click', () => {
    localStorage.setItem(dismissKey, '1');
    if (banner) banner.style.display = 'none';
  });

  window.addEventListener('load', () => {
    boot().catch((error) => console.error('PWA push boot gagal', error));
  });
})();
</script>
<?php endif; ?>
