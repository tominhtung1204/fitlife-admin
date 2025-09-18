@push('scripts')
    <script>
        (function ($) {
            $(document).ready(function () {
                $(document).on('change', '#language_id', function () {
                    var sub = $(this).val();
                    LanguageList(sub);
                });

                function LanguageList(sub) {          
                    var LanguageRoute = "{{ route('ajax-list', ['type' => 'language-list-data']) }}";
                    $.ajax({
                        url: LanguageRoute,
                        data: {
                            'id': sub,
                        },
                        success: function (result) {
                            if (result.results) {
                                if (sub != null) {
                                    $("#default_language_country_code").val(result.results.default_language_country_code);
                                    $("#default_language_code").val(result.results.default_language_code);
                                    $("#default_language_country_codeHidden").val(result.results.default_language_country_code); 
                                    $("#default_language_codeHidden").val(result.results.default_language_code); 
                                    $("#language_name").val(result.results.default_language_name);
                                }
                            } else {
                                console.log("No results found.");
                            }
                        }
                    });
                }
            });
        })(jQuery);
    </script>
@endpush
<x-app-layout :assets="$assets ?? []">
    <div>
        <?php $id = $id ?? null;?>
        @if(isset($id))
            {{ html()->modelForm($data, 'PATCH', route('languagelist.update', $id) )->attribute('enctype', 'multipart/form-data')->open() }}
        @else
            {{ html()->form('POST', route('languagelist.store'))->attribute('enctype', 'multipart/form-data')->open() }} 
        @endif
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                        <div class="card-action">
                            <a href="{{ route('languagelist.index') }} " class="btn btn-sm btn-primary" role="button">{{ __('message.back') }}</a>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="new-user-info">
                            <div class="row">
                                <div class="form-group col-md-4">
                                    {{ html()->label(__('message.language_list') . ' <span class="text-danger">*</span>', 'language_id')->class('form-control-label') }}
                                    {{ html()->select('language_id', isset($id) ? [optional($data->LanguageDefaultList)->id => optional($data->LanguageDefaultList)->default_language_name] : [], old('language_id'))
                                        ->class('select2js form-group languagelist')
                                        ->id('language_id')
                                        ->attribute('data-placeholder', __('message.select_name', ['select' => __('message.language')]))
                                        ->attribute('data-ajax--url', route('ajax-list', ['type' => 'languagelist']))
                                        ->attribute('required', 'required')
                                    }}
                                </div>
                                <div class="form-group col-md-4">
                                    {{ html()->label(__('message.language_name') . ' <span class="text-danger">*</span>', 'language_name')->class('form-control-label') }}
                                    {{ html()->text('language_name',old('language_name'))
                                        ->placeholder(__('message.language_name'))
                                        ->class('form-control')
                                        ->attribute('required', 'required')
                                    }}
                                </div>
                                <div class="form-group col-md-4">
                                    {{ html()->label(__('message.country_code') . ' <span class="text-danger">*</span>', 'country_code')->class('form-control-label') }}
                                    {{ html()->hidden('country_code', old('country_code'))->id('default_language_country_codeHidden') }}
                                    {{ html()->text('country_code',old('country_code'))
                                        ->placeholder(__('message.country_code'))
                                        ->class('form-control')
                                        ->attribute('required', 'required')
                                        ->id('default_language_country_code')
                                        ->attribute('disabled', 'disabled')
                                    }}
                                </div>
                                <div class="form-group col-md-4">
                                    {{ html()->label(__('message.language_code') . ' <span class="text-danger">*</span>', 'language_code')->class('form-control-label') }}
                                    {{ html()->hidden('language_code', old('language_code'))->id('default_language_codeHidden') }}
                                    {{ html()->text('language_code',old('language_code'))
                                        ->placeholder(__('message.language_code'))
                                        ->class('form-control')
                                        ->attribute('required', 'required')
                                        ->id('default_language_code')
                                        ->attribute('disabled', 'disabled')
                                    }}
                                </div>
                                <div class="form-group col-md-4">
                                    {{ html()->label(__('message.status') . ' <span class="text-danger">*</span>', 'status')->class('form-control-label') }}
                                    {{ html()->select('status', ['1' => __('message.active'), '0' => __('message.inactive')], old('status'))
                                        ->class('form-control select2js')
                                        ->attribute('required', 'required')
                                    }}
                                </div>
                                
                                <div class="form-group col-md-4">
                                    {{ html()->label(__('message.is_default'), 'is_default')->class('form-control-label') }}
                                    <div>
                                        {!! html()->hidden('is_default', 0)->class('form-check-input') !!}
                                        {!! html()->checkbox('is_default', null, 1)->class('form-check-input') !!}
                                    </div>
                                </div>                                

                                <div class="form-group col-md-4">
                                    <label class="form-control-label" for="image">{{ __('message.image') }} </label>
                                    <div class="">
                                        <input class="form-control file-input" type="file" name="language_image" accept="image/*">
                                    </div>
                                </div>
                                @if( isset($id) && getMediaFileExit($data, 'language_image'))
                                    <div class="col-md-2 mb-2 position-relative">
                                        <img id="language_image_preview" src="{{ getSingleMedia($data,'language_image') }}" alt="bodypart-image" class="avatar-100 mt-1">
                                        <a class="text-danger remove-file" href="{{ route('remove.file', ['id' => $data->id, 'type' => 'language_image']) }}"
                                            data--submit='confirm_form'
                                            data--confirmation='true'
                                            data--ajax='true'
                                            data-toggle='tooltip'
                                            title='{{ __("message.remove_file_title" , ["name" =>  __("message.image") ]) }}'
                                            data-title='{{ __("message.remove_file_title" , ["name" =>  __("message.image") ]) }}'
                                            data-message='{{ __("message.remove_file_msg") }}'
                                        >
                                            <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path opacity="0.4" d="M16.34 1.99976H7.67C4.28 1.99976 2 4.37976 2 7.91976V16.0898C2 19.6198 4.28 21.9998 7.67 21.9998H16.34C19.73 21.9998 22 19.6198 22 16.0898V7.91976C22 4.37976 19.73 1.99976 16.34 1.99976Z" fill="currentColor"></path>
                                                <path d="M15.0158 13.7703L13.2368 11.9923L15.0148 10.2143C15.3568 9.87326 15.3568 9.31826 15.0148 8.97726C14.6728 8.63326 14.1198 8.63426 13.7778 8.97626L11.9988 10.7543L10.2198 8.97426C9.87782 8.63226 9.32382 8.63426 8.98182 8.97426C8.64082 9.31626 8.64082 9.87126 8.98182 10.2123L10.7618 11.9923L8.98582 13.7673C8.64382 14.1093 8.64382 14.6643 8.98582 15.0043C9.15682 15.1763 9.37982 15.2613 9.60382 15.2613C9.82882 15.2613 10.0518 15.1763 10.2228 15.0053L11.9988 13.2293L13.7788 15.0083C13.9498 15.1793 14.1728 15.2643 14.3968 15.2643C14.6208 15.2643 14.8448 15.1783 15.0158 15.0083C15.3578 14.6663 15.3578 14.1123 15.0158 13.7703Z" fill="currentColor"></path>
                                            </svg>
                                        </a>
                                    </div>
                                @endif
                            </div>
                            <hr>
                            {{ html()->submit( __('message.save') )->class('btn btn-md btn-primary float-end') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @if(isset($id))
            {{ html()->closeModelForm() }}
        @else
            {{ html()->form()->close() }}
        @endif
    </div>
</x-app-layout>
