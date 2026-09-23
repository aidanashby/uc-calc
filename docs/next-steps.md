# Next steps: UC Calculator

What's left to do after the session on 22nd September 2026, in order. Tick each box as you go. Delete this file once everything is done.

---

## 1. Review and merge pull request #2

The routine reads its instructions from `main`, so this must be merged before its first run on **7th December 2026**.

- [ ] Open [PR #2](https://github.com/aidanashby/uc-calc/pull/2) and read the summary.
- [ ] Skim the spec changes in `uc-calculator-spec.md`, especially §5 (benefit cap), §8 (footer copy) and §13 (QA scenarios).
- [ ] Mark the PR **Ready for review**, then **Merge**.

Merging does **not** release a new version. Your live site is unaffected until step 5.

## 2. Allow the source websites in the cloud environment

The routine runs in your **Default Cloud Environment**. At the moment it can only reach package registries and GitHub, so it cannot open gov.uk, Ofgem or any other source. Every source site tested on 22nd September was blocked.

- [ ] Go to [claude.ai/code/routines](https://claude.ai/code/routines) and click **UC Calculator monitoring**.
- [ ] Open the menu next to the routine's name and select **Edit**.
- [ ] Below the **Instructions** box, click the cloud icon showing **Default Cloud Environment**.
- [ ] Hover over **Default Cloud Environment** and click the settings (gear) icon on the right.
- [ ] In the **Update cloud environment** dialog, set **Network access** to **Custom**.
- [ ] Tick **Also include default list of common package managers**. Without it, `npm ci` and GitHub-related tooling in future sessions will break.
- [ ] Paste this into **Allowed domains** (one per line; a leading `*.` covers every subdomain):

```text
*.gov.uk
*.parliament.uk
*.ofcom.org.uk
*.bristolwater.co.uk
*.wessexwater.co.uk
*.water.org.uk
*.firstbus.co.uk
*.tvlicensing.co.uk
cpag.org.uk
*.cpag.org.uk
*.rightsnet.org.uk
*.turn2us.org.uk
*.entitledto.co.uk
*.citizensadvice.org.uk
ifs.org.uk
*.ifs.org.uk
*.resolutionfoundation.org
*.jrf.org.uk
*.trussell.org.uk
*.trusselltrust.org
policyinpractice.co.uk
*.policyinpractice.co.uk
*.moneysavingexpert.com
```

- [ ] Click **Save changes**. It applies from the next run or session.

`*.gov.uk` covers gov.uk, legislation.gov.uk, Ofgem, ONS, the West of England Combined Authority and the Department for Education blog. This change applies to every session in the Default Cloud Environment, not just the routine. All the domains are read-only public information sites, so the risk is low. If you'd rather keep them separate, create a new environment with these settings and pick it in the routine's Edit form instead.

## 3. Attach the repository to the routine

The routine was created from this session without a repository attached. It is told to attach one itself, but setting it in the form is more reliable.

- [ ] Still in the routine's **Edit** form, under repositories, add **aidanashby/uc-calc**.
- [ ] Under **Connectors**, remove any connectors listed. The routine doesn't need any.
- [ ] Check the schedule reads as the cron `0 8 7 1,3,4,6,9,12 *`: 08:00 UTC (8am in winter, 9am in summer) on the 7th of January, March, April, June, September and December. Push and email notifications are on.
- [ ] Save.

## 4. Test the routine

Do this after steps 1 to 3.

- [ ] On the routine's page, click **Run now**.
- [ ] When it finishes, open the run and read the final message. You should get a push notification and an email with the same text.
- [ ] Check for:
  - **No line about blocked domains.** If there is one, add the named domain to the list in step 2.
  - **Either "no changes needed", or a new file in `reports/uc-updates/` on `main`.** If `main` is protected on GitHub, it will open a draft PR instead and say so.
- [ ] If a report was written, read it. Anything it recommends is for you to apply by hand (step 5).

A green status on the run only means it didn't crash. Always read the final message.

## 5. Release the updated plugin (when you're ready)

Nothing has been released yet. The live site is still on v1.0.0. When you want the new version live:

- [ ] In `uc-calc.php`, change both `Version: 1.0.0` and `define( 'UC_CALC_VERSION', '1.0.0' );` to `1.1.0`.
- [ ] Run `npm test` and `npm run build`, commit and push to `main`.
- [ ] Push the tag: `git tag v1.1.0 && git push origin v1.1.0`. The **Release** GitHub Action checks everything and publishes the release with `uc-calc.zip`. Watch it under the repository's **Actions** tab; this is its first ever run.
- [ ] On the live site, go to **Dashboard → Updates**, click **Check again**, and update UC Calculator.

This first update comes from v1.0.0's updater, which only checks for updates while you're in wp-admin. Clicking **Check again** handles that. From v1.1.0 onwards, updates are also found by WordPress's background checks and WP-CLI, and automatic updates work.

## 6. Check the live site's saved settings

Saved settings override the plugin's defaults. v1.0.0 stored every field whenever anyone clicked **Save Settings**, so old figures (such as the 673 work allowance) may be stored and still in use. From v1.1.0 the page marks any field that differs from the default, and only changed fields are saved.

- [ ] Go to **Settings → UC Calculator**.
- [ ] Look for fields marked **Changed from the default of …**. **Work allowance — no housing (monthly)** showing 673 is one of these.
- [ ] If none of the marked values were set on purpose, click **Reset to defaults**. Otherwise change the unwanted ones to their default and click **Save Settings**. Either way, fields left at the default now follow future plugin updates.
- [ ] Open the calculator page and check:
  - a single adult aged 25 or over shows **£98.05** income and **£19.41** shortfall;
  - two adults in a couple with three children shows **£360.61** income and the benefit cap note.

## 7. Optional

- [ ] On the **Plugins** screen, click **Enable auto-updates** for UC Calculator (works from v1.1.0).
- [ ] Delete this file.
