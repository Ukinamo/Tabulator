# Design System Specification: High-Octane Tabulation

## 1. Overview & Creative North Star: "The Neon Auditor"
The "Neon Auditor" aesthetic rejects the sterile, grayscale nature of traditional administrative software. This design system balances the cold, absolute precision of competitive tabulation with the high-energy glamour of a live stage production. 

**The Creative North Star** is centered on **Intentional Luminescence**. We break the "SaaS template" look by utilizing deep, midnight voids (Sidebar/MC Screen) contrasted against airy, high-key workspaces. We avoid rigid grids in favor of **Layered Depth**—where data isn't just displayed in a table, but presented on "lit" stages of varying elevation. By using asymmetric focal points and glassmorphism, we ensure the UI feels like a high-end broadcast suite rather than a spreadsheet.

---

## 2. Colors & Surface Architecture
We move beyond flat hex codes to a functional token system that drives mood and hierarchy.

### 2.1 The Palette
- **Primary (`#b40066`)**: The pulse of the system. Used for critical actions and brand identity.
- **Secondary (`#4a5e86`)**: Used for supportive UI elements and grounding the vibrant primary.
- **Tertiary/Accent (`#006a3d`)**: Specifically reserved for success states, scores, and "Go" signals.
- **Neutral/Surface**: Ranging from `surface-container-lowest` (#ffffff) to `surface-dim` (#d1d8ff).

### 2.2 The "No-Line" Rule
**Explicit Instruction:** Designers are prohibited from using 1px solid borders to define sections. 
Structure must be achieved through:
1.  **Background Color Shifts:** Use `surface-container-low` for a section background and `surface-container-lowest` for the cards sitting atop it.
2.  **Generous Negative Space:** Use our spacing scale (specifically `8` to `12`) to separate functional groups.

### 2.3 Surface Hierarchy & Nesting
Treat the UI as a physical stack of materials. 
- **Base Level:** `background` (#faf8ff) or `surface`.
- **In-Page Containers:** `surface-container-low`.
- **Interactive Cards:** `surface-container-lowest`.
- **Theatrical Elements (MC Screen):** Utilize `on-surface` (#0e193d) as a background to make `primary` and `tertiary` tokens glow.

### 2.4 The "Glass & Gradient" Rule
For "Stage" screens (Login/MC), apply **Glassmorphism**: 
- `background-color`: semi-transparent `surface` or `primary-container`.
- `backdrop-filter`: blur(12px).
- **Signature Texture:** Apply a subtle linear gradient to Primary buttons: `primary` (#b40066) to `primary-container` (#da2180) at a 135-degree angle. This adds "soul" and mimics stage lighting.

---

## 3. Typography: Editorial Authority
The interplay between **Plus Jakarta Sans** (Headings) and **Inter** (Data) creates a high-contrast, editorial feel.

- **Display (Plus Jakarta Sans):** Used for MC Screens and massive score reveals. Bold, tight letter-spacing (-0.02em).
- **Headline (Plus Jakarta Sans):** Used for page titles. Conveys the "Main Event" energy.
- **Body (Inter):** High-legibility sans-serif. Used for all tabulation data, ensuring that even at `body-sm`, numbers are crystal clear.
- **Label (Inter):** Uppercase with increased letter-spacing (0.05em) for secondary metadata.

---

## 4. Elevation & Depth: Tonal Layering
We eschew the 2010s-era drop shadow in favor of **Tonal Elevation**.

- **The Layering Principle:** Depth is achieved by "stacking" surface-container tiers. A `surface-container-lowest` card on a `surface-container-low` section creates a soft, natural lift without cluttering the UI with lines.
- **Ambient Shadows:** For floating modals, use a shadow with a 24px-32px blur at 6% opacity, tinted with the `on-surface` color.
- **The "Ghost Border":** If a boundary is legally required for accessibility, use the `outline-variant` token at **15% opacity**. Never 100%.
- **Theatrical Depth:** On the MC Screen, use `surface-bright` highlights on the top edge of cards to simulate top-down stage lighting.

---

## 5. Component Signature Styles

### 5.1 Buttons & Inputs
- **Primary Button:** Gradient-filled (`primary` to `primary-container`), `full` roundedness (pills), and `headline-sm` typography for high impact.
- **Secondary Button:** `surface-container-highest` background with `on-surface` text. No border.
- **Input Fields:** `sm` (0.5rem) roundedness. Use `surface-container-low` for the fill. On focus, transition the background to `surface-container-lowest` and apply a 2px `primary` "glow" shadow (15% opacity).

### 5.2 Theatrical Result Cards (MC Screen)
- **Container:** Midnight background (`on-surface`) with a 1px "Ghost Border" at 10% opacity.
- **Rank Badge:** Use `primary` (#b40066) with a `display-sm` weight.
- **Score Display:** Use `tertiary-fixed` (#57ffa6) for the scores—this "minty" green must feel like a digital scoreboard.
- **Animation:** Content should slide in from the bottom with a `cubic-bezier(0.16, 1, 0.3, 1)` easing.

### 5.3 Data Tables
- **Forbid Divider Lines.**
- **Alternating Rows:** Use `surface-container-low` and `surface-container-lowest`. 
- **Spacing:** Minimum `2` (0.7rem) vertical padding for rows to allow data to breathe.
- **Highlighting:** The "Leader" row should utilize a subtle `primary-fixed` (#ffd9e3) background tint.

---

## 6. Do’s and Don’ts

### Do:
- **Use Asymmetry:** Place the primary action button slightly offset from the grid to create a modern, bespoke feel.
- **Embrace White Space:** If a section feels "crowded," double the padding using the `8` (2.75rem) spacing token.
- **Color-Code Logic:** Only use `tertiary` for confirmed, tabulated scores. Use `secondary` for pending data.

### Don't:
- **No 1px Borders:** Never use a solid line to separate table rows or sidebar items. Use color shifts.
- **No Grey Shadows:** Shadows must always be tinted with the surface color to avoid looking "dirty."
- **No Default Fonts:** Never fallback to system fonts; the contrast between Plus Jakarta Sans and Inter is the system's identity.
- **No Flat Admin Tables:** Avoid the "Excel" look. Every table is a leaderboard; treat it with theatrical importance.