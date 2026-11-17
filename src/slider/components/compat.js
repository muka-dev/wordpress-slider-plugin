/**
 * Stable-or-experimental lookups for components that were only exported with
 * an `__experimental` prefix in WordPress 6.6.
 *
 * `ToggleGroupControl` and `UnitControl` have no stable export in
 * `@wordpress/components` of WP 6.6; the stable names arrive in later
 * releases. We use the stable name when present and fall back to the
 * experimental one.
 */
import * as components from '@wordpress/components';

export const ToggleGroupControl =
	components.ToggleGroupControl ??
	components.__experimentalToggleGroupControl;
export const ToggleGroupControlOption =
	components.ToggleGroupControlOption ??
	components.__experimentalToggleGroupControlOption;
export const UnitControl =
	components.UnitControl ?? components.__experimentalUnitControl;
