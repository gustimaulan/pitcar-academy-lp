const base = (import.meta.env.PUBLIC_API_BASE_URL ?? import.meta.env.PUBLIC_LEAD_API_BASE_URL ?? '')
  .trim()
  .replace(/\/$/, '');
const KEY = 'pitcar-learn-session';

export const session = {
  get(): string | null {
    try { return localStorage.getItem(KEY); } catch { return null; }
  },
  set(token: string) {
    try { localStorage.setItem(KEY, token); } catch { /* storage blocked: the visit stays signed out */ }
  },
  clear() {
    try { localStorage.removeItem(KEY); } catch { /* nothing stored */ }
  },
};

export class ApiError extends Error {
  constructor(public status: number, message: string, public errors?: Record<string, string[]>) {
    super(message);
  }
}

export function loginUrl(): string {
  return '/belajar/masuk/?next=' + encodeURIComponent(location.pathname + location.search);
}

/** Sends the visitor to the login page when there is no session. Returns false in that case. */
export function requireSession(): boolean {
  if (session.get()) return true;
  location.replace(loginUrl());
  return false;
}

type Options = { method?: string; body?: unknown; auth?: boolean; keepalive?: boolean };

export async function api<T>(path: string, { method = 'GET', body, auth = true, keepalive }: Options = {}): Promise<T> {
  const headers: Record<string, string> = { Accept: 'application/json' };
  if (body !== undefined) headers['Content-Type'] = 'application/json';
  const token = session.get();
  if (auth && token) headers.Authorization = `Bearer ${token}`;

  let res: Response;
  try {
    res = await fetch(`${base}/api${path}`, { method, headers, keepalive, body: body === undefined ? undefined : JSON.stringify(body) });
  } catch {
    throw new ApiError(0, 'Tidak bisa terhubung ke server. Periksa koneksi internetmu, lalu coba lagi.');
  }

  if (res.status === 401 && auth) {
    session.clear();
    location.replace(loginUrl());
  }

  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new ApiError(res.status, data.message ?? 'Terjadi kesalahan. Coba lagi sebentar lagi.', data.errors);
  return data as T;
}

/** Attachments need the session header, so they are fetched rather than linked. */
export async function fetchFile(id: number): Promise<Blob> {
  let res: Response;
  try {
    res = await fetch(`${base}/api/learn/attachments/${id}`, { headers: { Authorization: `Bearer ${session.get() ?? ''}` } });
  } catch {
    throw new ApiError(0, 'Tidak bisa terhubung ke server. Periksa koneksi internetmu, lalu coba lagi.');
  }
  if (res.status === 401) {
    session.clear();
    location.replace(loginUrl());
  }
  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    throw new ApiError(res.status, data.message ?? 'Berkas tidak bisa dibuka. Coba lagi sebentar lagi.');
  }
  return res.blob();
}

/** Hands a fetched file to the browser as a download. */
export function saveFile(blob: Blob, name: string): void {
  const url = URL.createObjectURL(blob);
  const link = Object.assign(document.createElement('a'), { href: url, download: name });
  document.body.append(link);
  link.click();
  link.remove();
  setTimeout(() => URL.revokeObjectURL(url), 10_000);
}

export async function download(id: number, name: string): Promise<void> {
  saveFile(await fetchFile(id), name);
}
