export interface Coordinates {
  latitude: number
  longitude: number
}

const WKB_POINT = 1
const EWKB_SRID_FLAG = 0x20000000

/**
 * Decodes a PostGIS point returned as hex (E)WKB, which is how the API
 * serialises the `reports.location` geography column.
 * Returns null for anything that is not a 2D point.
 */
export function parseWkbPoint(hex: string | null | undefined): Coordinates | null {
  if (!hex || !/^[0-9a-fA-F]+$/.test(hex) || hex.length % 2 !== 0) {
    return null
  }

  const bytes = new Uint8Array(hex.length / 2)
  for (let i = 0; i < bytes.length; i++) {
    bytes[i] = parseInt(hex.slice(i * 2, i * 2 + 2), 16)
  }

  const view = new DataView(bytes.buffer)
  if (bytes.length < 5) return null

  const littleEndian = view.getUint8(0) === 1
  const type = view.getUint32(1, littleEndian)
  let offset = 5

  if (type & EWKB_SRID_FLAG) {
    offset += 4
  }

  // Only plain 2D points (no Z/M flags) are expected.
  if ((type & 0x0fffffff) !== WKB_POINT || bytes.length < offset + 16) {
    return null
  }

  const longitude = view.getFloat64(offset, littleEndian)
  const latitude = view.getFloat64(offset + 8, littleEndian)

  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
    return null
  }

  return { latitude, longitude }
}

export function formatCoordinates(coords: Coordinates): string {
  return `${coords.latitude.toFixed(6)}, ${coords.longitude.toFixed(6)}`
}

export function openStreetMapUrl(coords: Coordinates): string {
  return `https://www.openstreetmap.org/?mlat=${coords.latitude}&mlon=${coords.longitude}#map=18/${coords.latitude}/${coords.longitude}`
}
