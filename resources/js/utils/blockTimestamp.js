/**
 * @param {number} unixSeconds
 */
export function formatBlockFullDateTime(unixSeconds) {
    return new Date(unixSeconds * 1000).toLocaleString(undefined, {
        dateStyle: 'full',
        timeStyle: 'medium',
    });
}

/**
 * Human-friendly relative time for recent blocks; falls back to a medium date for older times.
 *
 * @param {number} unixSeconds
 * @param {number} [_refreshToken] Bumps when the UI should recompute (e.g. once per minute).
 */
export function formatBlockRelativeTime(unixSeconds, _refreshToken = 0) {
    void _refreshToken;
    const then = unixSeconds * 1000;
    const diffMs = Date.now() - then;

    if (diffMs < 0) {
        return formatBlockFullDateTime(unixSeconds);
    }

    const sec = Math.floor(diffMs / 1000);

    if (sec < 10) {
        return 'just now';
    }

    if (sec < 60) {
        return `${sec} seconds ago`;
    }

    const min = Math.floor(sec / 60);

    if (min < 60) {
        return min === 1 ? 'a minute ago' : `${min} minutes ago`;
    }

    const hr = Math.floor(min / 60);

    if (hr < 24) {
        return hr === 1 ? 'an hour ago' : `${hr} hours ago`;
    }

    const day = Math.floor(hr / 24);

    if (day < 7) {
        return day === 1 ? 'a day ago' : `${day} days ago`;
    }

    return new Date(then).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}
