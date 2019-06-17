<?php

namespace Tests\Unit\Controllers\User;

use App\Models\SubIdx;
use App\Models\SubIdxBatch\SubIdxBatch;
use App\Models\SubIdxLanguage;
use App\Support\Facades\VobSub2Srt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubIdxBatchStartControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var SubIdxBatch $subIdxBatch */
    private $subIdxBatch;

    public function settingUp()
    {
        $this->subIdxBatch = $this->createSubIdxBatch();
    }

    /** @test */
    function it_can_show_a_batch()
    {
        $this->createSubIdxBatchFiles(3, $this->subIdxBatch);

        $this->actingAs($this->subIdxBatch->user)
            ->showStart($this->subIdxBatch)
            ->assertStatus(200)
            ->assertDontSeeText('buy more');
    }

    /** @test */
    function it_can_show_a_batch_when_you_dont_have_enough_tokens()
    {
        $this->createSubIdxBatchFiles(3, $this->subIdxBatch);

        $this->subIdxBatch->user->update(['batch_tokens_left' => 2]);

        $this->actingAs($this->subIdxBatch->user)
            ->showStart($this->subIdxBatch)
            ->assertStatus(200)
            ->assertSeeText('buy more');
    }

    /** @test */
    function it_redirects_if_the_batch_has_already_started()
    {
        $subIdxBatch = $this->createSubIdxBatch(['started_at' => now()]);

        $this->actingAs($subIdxBatch->user)
            ->showStart($subIdxBatch)
            ->assertRedirect(route('user.subIdxBatch.show', $subIdxBatch));
    }

    /** @test */
    function it_can_show_an_empty_batch()
    {
        $this->actingAs($this->subIdxBatch->user)
            ->showStart($this->subIdxBatch)
            ->assertStatus(200)
            ->assertSee('to this batch yet');
    }

    /** @test */
    function it_will_only_show_your_own_batches()
    {
        $anotherUser = $this->createUser();

        $this->actingAs($anotherUser)
            ->showStart($this->subIdxBatch)
            ->assertStatus(403);
    }

    /** @test */
    function it_will_only_start_your_own_batches()
    {
        $anotherUser = $this->createUser();

        $this->actingAs($anotherUser)
            ->postStart($this->subIdxBatch, [])
            ->assertStatus(403);
    }

    /** @test */
    function you_cant_start_an_already_started_batch()
    {
        $this->subIdxBatch->update(['started_at' => now()]);

        $this->actingAs($this->subIdxBatch->user)
            ->postStart($this->subIdxBatch, [])
            ->assertStatus(422);
    }

    /** @test */
    function you_cant_start_a_batch_if_you_dont_have_enough_tokens()
    {
        $this->createSubIdxBatchFiles(3, $this->subIdxBatch);

        $this->subIdxBatch->user->update(['batch_tokens_left' => 2]);

        $this->actingAs($this->subIdxBatch->user)
            ->postStart($this->subIdxBatch, ['en'])
            ->assertStatus(422);
    }

    /** @test */
    function you_have_to_post_at_least_one_language()
    {
        VobSub2Srt::fake();

        $this->actingAs($this->subIdxBatch->user)
            ->postStart($this->subIdxBatch, [])
            ->assertSessionHasErrors()
            ->assertStatus(302);
    }

    /** @test */
    function you_cant_post_duplicate_languages()
    {
        VobSub2Srt::fake();

        $this->createSubIdxBatchFiles(3, $this->subIdxBatch);

        $this->setBatchLanguages($this->subIdxBatch, ['en', 'nl', 'es']);

        $this->actingAs($this->subIdxBatch->user)
            ->postStart($this->subIdxBatch, ['en', 'nl', 'en'])
            ->assertSessionHasErrors()
            ->assertStatus(302);
    }

    /** @test */
    function you_can_only_posts_available_languages()
    {
        VobSub2Srt::fake();

        $this->createSubIdxBatchFiles(3, $this->subIdxBatch);

        $this->setBatchLanguages($this->subIdxBatch, ['en', 'nl', 'es']);

        $this->actingAs($this->subIdxBatch->user)
            ->postStart($this->subIdxBatch, ['en', 'nl', 'en'])
            ->assertSessionHasErrors()
            ->assertStatus(302);
    }

    /** @test */
    function it_can_start_a_batch()
    {
        VobSub2Srt::fakeExtracting();

        $batchFile = $this->createSubIdxBatchFile($this->subIdxBatch);

        $user = $this->subIdxBatch->user;

        $user->update([
            'batch_tokens_left' => 1,
            'batch_tokens_used' => 15,
        ]);

        $this->copyRealFileToStorage('sub-idx/many.sub', $batchFile->sub_storage_file_path);
        $this->copyRealFileToStorage('sub-idx/many.idx', $batchFile->idx_storage_file_path);

        Storage::assertExists("sub-idx-batches/{$this->subIdxBatch->user_id}/{$this->subIdxBatch->id}");

        $this->assertNull($this->subIdxBatch->finished_at);

        $this->actingAs($user)
            ->postStart($this->subIdxBatch, ['en', 'pl'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('user.subIdxBatch.show', $this->subIdxBatch));

        $this->assertNotNull($this->subIdxBatch->refresh()->started_at);
        $this->assertCount(0, $this->subIdxBatch->files);
        $this->assertCount(0, $this->subIdxBatch->unlinkedFiles);

        $this->assertCount(1, $this->subIdxBatch->subIdxes);

        /** @var $subIdx SubIdx */
        $subIdx = $this->subIdxBatch->subIdxes->first();

        $this->assertCount(2, $subIdx->languages);

        $this->assertSame($batchFile->sub_original_name, $subIdx->original_name);
        $this->assertSame('a', $subIdx->filename);
        $this->assertNotNull($subIdx->url_key);

        /** @var SubIdxLanguage $plLanguage */
        /** @var SubIdxLanguage $enLanguage */
        $plLanguage = $subIdx->languages->where('language', 'pl')->first();
        $enLanguage = $subIdx->languages->where('language', 'en')->first();

        $this->assertSame('19', $plLanguage->index);
        $this->assertSame('0', $enLanguage->index);

        $this->assertNotNull($plLanguage->queued_at);
        $this->assertNotNull($enLanguage->queued_at);

        $this->assertNotNull($plLanguage->finished_at);
        $this->assertNotNull($enLanguage->finished_at);

        $this->assertNotNull($this->subIdxBatch->refresh()->finished_at);

        // A listener cleans up all the source sub/idx files when the batch is done.
        Storage::assertMissing("sub-idx-batches/{$this->subIdxBatch->user_id}/{$this->subIdxBatch->id}");
        Storage::assertExists("sub-idx-batches/{$this->subIdxBatch->user_id}");
        $this->assertFileNotExists($subIdx->file_path_without_extension.'.sub');
        $this->assertFileNotExists($subIdx->file_path_without_extension.'.idx');

        $this->assertSame(0, $user->refresh()->batch_tokens_left);
        $this->assertSame(16, $user->batch_tokens_used);
    }

    private function showStart($subIdxBatch)
    {
        return $this->get(route('user.subIdxBatch.showStart', $subIdxBatch));
    }

    private function postStart($subIdxBatch, array $languages)
    {
        return $this->post(route('user.subIdxBatch.start', $subIdxBatch), ['languages' => $languages]);
    }

    private function setBatchLanguages(SubIdxBatch $subIdxBatch, $languages = [])
    {
        $batchFile = $subIdxBatch->files->first();

        if (! $batchFile) {
            $this->fail('called "setBatchLanguages()" on a batch with no files');
        }

        Cache::rememberForever($batchFile->id, function () use ($languages) {
            return array_map(function ($language) {
                return ['index' => 0, 'language' => $language];
            }, $languages);
        });
    }
}