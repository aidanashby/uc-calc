# Your to-do list: UC Calculator

Everything left for you to do, in order, as of 23rd September 2026. v1.1.0 is released on GitHub, but the live site stays on v1.0.0 until you update it. Tick each box as you go.

---

## 1. Test v1.1.0 on the dev site

None of the 23rd September fixes have been tested in a browser yet.

- [ ] In the dev site's wp-admin, go to **Dashboard → Updates**, click **Check again**, and update UC Calculator. If it isn't installed there, download `uc-calc.zip` from the [v1.1.0 release](https://github.com/aidanashby/uc-calc/releases/tag/v1.1.0) and upload it under **Plugins → Add New → Upload Plugin**.

**Settings page** (**Settings → UC Calculator**)

- [ ] Change one value and click **Save Settings**. Only that field should show **Changed from the default of …** beneath it.
- [ ] Click **Reset to defaults**. A confirmation box should appear. After confirming, no field should show a "changed" note.

**Calculator page**

- [ ] With one adult aged 25 or over and no children, the result shows income **£98.05**, essentials **£117.46** and a shortfall of **£19.41**.
- [ ] With two adults in a couple and three children aged 5 to 15, income shows **£360.61** and a note about the benefit cap.
- [ ] Click an ⓘ button in the essentials list. Its explanation opens below the row. Click again and it closes. Try it on a phone too.
- [ ] The small "Income per week" and "Essentials per week" labels are dark green and easy to read.
- [ ] Screen reader check with NVDA (free, Windows): press Tab to reach **One more adult**, then press Enter. NVDA should read out the new number.

**LiteSpeed Cache**

- [ ] Go to **LiteSpeed Cache → Page Optimization → JS Settings** and note what **Load JS Deferred** is set to.
- [ ] Set it to **Delayed**, save, and purge the cache.
- [ ] Open the calculator page in a private window. Move the mouse or tap the screen, then check the calculator responds.
- [ ] Put **Load JS Deferred** back to what it was, and purge again.

If anything fails, don't update the live site. Tell Claude what you saw, and the fix will go out as v1.1.1.

## 2. Update the live site

- [ ] In the live site's wp-admin, go to **Dashboard → Updates**, click **Check again**, and update UC Calculator. v1.0.0 only looks for updates while you're in wp-admin, so **Check again** matters this once. From v1.1.0, WordPress finds updates in the background too.
- [ ] Go to **Settings → UC Calculator** and look for fields marked **Changed from the default of …**. You'll probably see **Work allowance — no housing (monthly)** showing 673, which is expected.
- [ ] If none of the marked values were set on purpose, click **Reset to defaults**. Otherwise change the unwanted ones back to their default and click **Save Settings**.
- [ ] Open the calculator page and repeat the two figure checks from step 1 (£98.05 and £360.61).

## 3. Set up the monitoring routine (before 7th December 2026)

The routine checks for changes to UC rates and costs. It first runs on **7th December 2026**, and at the moment it can't reach any of the websites it needs.

**Let it reach the source websites**

- [ ] Go to [claude.ai/code/routines](https://claude.ai/code/routines) and click **UC Calculator monitoring**.
- [ ] Open the menu next to the routine's name and select **Edit**.
- [ ] Below the **Instructions** box, click the cloud icon showing **Default Cloud Environment**.
- [ ] Hover over **Default Cloud Environment** and click the settings (gear) icon on the right.
- [ ] Set **Network access** to **Custom**.
- [ ] Tick **Also include default list of common package managers**. Without it, `npm ci` and GitHub tools in future cloud sessions will break.
- [ ] Paste this into **Allowed domains**:

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

- [ ] Click **Save changes**.

This setting applies to every session in the Default Cloud Environment, not just the routine. The sites are all read-only public information, so the risk is low. If you'd rather keep them separate, create a new environment with these settings and pick it in the routine's **Edit** form instead.

**Attach the repository**

- [ ] Still in the routine's **Edit** form, add the repository **aidanashby/uc-calc**.
- [ ] Under **Connectors**, remove any that are listed.
- [ ] Check the schedule is `0 8 7 1,3,4,6,9,12 *` (8am UTC on the 7th of January, March, April, June, September and December).
- [ ] Save.

**Test it**

- [ ] On the routine's page, click **Run now**.
- [ ] When it finishes, read its final message (it also arrives as a push notification and an email).
- [ ] If the message names a blocked domain, add that domain to the list above and run it again.
- [ ] The message should either say no changes are needed, or point to a new report in `reports/uc-updates/` on `main` (or a draft PR, if `main` is protected by then).
- [ ] If it wrote a report, read it. Anything it recommends is for you to apply, or ask Claude to.

A green tick on the run only means it didn't crash. Always read the message.

## 4. GitHub security

From [issue #4](https://github.com/aidanashby/uc-calc/issues/4). The plugin updates itself from GitHub, so whoever controls the repo controls the live site's code.

- [ ] Check two-factor authentication is on for your GitHub account: **Settings → Password and authentication**.
- [ ] Ask Claude to set up rules protecting `main` and release tags. This is quick, but it changes who can push, so it's worth doing together.
- [ ] Optional, once the two above are done: on the live site's **Plugins** screen, click **Enable auto-updates** for UC Calculator.

## 5. Decisions waiting on you

No rush, but these need your judgement, not code.

- [ ] [Issue #3](https://github.com/aidanashby/uc-calc/issues/3): three modelling choices a critic could challenge, and whether the results fit the "UC routinely falls short" message.
- [ ] [Issue #5](https://github.com/aidanashby/uc-calc/issues/5): whether to change the mid-green focus outline to dark green in the essentials list, so it meets contrast rules.

---

When every box is ticked, delete this file.
