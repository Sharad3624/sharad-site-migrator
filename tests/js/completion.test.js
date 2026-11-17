const test = require('node:test');
const assert = require('node:assert/strict');
const { boot } = require('./harness');

for (const bundle of ['import.min.js', 'backups.min.js']) {
  test(`${bundle}: done status shows success and stops polling`, async () => {
    const ctx = await boot(bundle);
    try {
      let requests = 0;
      ctx.$.ajax = () => {
        requests++;
        return ctx.$.Deferred().resolve({ type: 'done', title: 'Your site has been imported successfully!', message: 'Finished.' }).promise();
      };
      const importer = new ctx.w.Shm.Import();
      importer.setStatus({ type: 'info', message: 'Importing...' });
      const before = ctx.delays.length;
      importer.getStatus();
      assert.equal(requests, 1);
      assert.equal(ctx.delays.length, before, 'completion must not schedule another status request');
      assert.match(ctx.w.document.body.textContent, /Your site has been imported successfully!/);
      assert.doesNotMatch(ctx.w.document.body.textContent, /Importing\.\.\./);
    } finally {
      ctx.w.close();
    }
  });
}
