import { describe, expect, it } from 'vitest'
import { formatCoordinates, parseWkbPoint } from './location'

describe('parseWkbPoint', () => {
  it('decodes a little-endian EWKB point with SRID 4326 as returned by the API', () => {
    // Real value from the local API for a Kolkata demo report.
    const coords = parseWkbPoint('0101000020E6100000ECC039234A1756408AB0E1E995923640')
    expect(coords).not.toBeNull()
    expect(coords!.longitude).toBeCloseTo(88.3639, 4)
    expect(coords!.latitude).toBeCloseTo(22.5726, 4)
  })

  it('decodes a plain WKB point without SRID', () => {
    const coords = parseWkbPoint('0101000000ECC039234A1756408AB0E1E995923640')
    expect(coords!.longitude).toBeCloseTo(88.3639, 4)
    expect(coords!.latitude).toBeCloseTo(22.5726, 4)
  })

  it('decodes a big-endian WKB point', () => {
    // POINT(1 2), big-endian.
    const coords = parseWkbPoint('00000000013FF00000000000004000000000000000')
    expect(coords).toEqual({ longitude: 1, latitude: 2 })
  })

  it.each([null, undefined, '', 'not-hex', '0102', '010200000000'])('returns null for %s', (value) => {
    expect(parseWkbPoint(value)).toBeNull()
  })

  it('formats coordinates with six decimals', () => {
    expect(formatCoordinates({ latitude: 22.5726, longitude: 88.3639 })).toBe('22.572600, 88.363900')
  })
})
