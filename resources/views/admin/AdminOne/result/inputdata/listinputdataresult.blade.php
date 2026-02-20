<?php $no = 0;?>
@forelse($results as $view_data)
    <?php
        $no++ ;
        $id = $view_data['id'];
        $id = str_replace('-','',$id);
    ?>

    <tr>
        <td style="text-align:center;" id="hg_td">{{$no}}</td>
        <td style="text-align:center;">{{ number_format($view_data['heat_line']['heat']['nomor_seri'],0,"","") }}</td>
        <td style="text-align:left;">{{$view_data['atlet']['nama']}}</td>
        <td style="text-align:center;">{{ number_format($view_data['heat_line']['line_number'],0,"","") }}</td>
        <td style="text-align:center;">{{$view_data['heat_line']['best_time']}}</td>
        <td style="text-align:center;">
            <input 
                type="text" 
                name="new_hasil_{{$id}}" 
                value="{{$view_data['hasil']}}" 
                style="width: 95px; text-align:center;" 
                onkeypress="return goodchars(event,'0123456789:.',this)"
            >
        </td>
        <td style="text-align:center;">Foto Hasil</td>
        <td style="text-align:center;">{{$view_data['ranking']}}</td>
        <td style="text-align:center;">Point</td>           
    </tr>

    <script type="text/javascript">
        $(document).ready(function(){
            $('.bg_act_page_main button').prop({disabled:false});

            $('input[name="new_hasil_{{$id}}"]').change(function(){
                var code_data = "{{ $view_data['code_data'] }}";
                var code_championship = $('input[name="code_championship"]').val();
                var code_event = $('input[name="code_event"]').val();
                var code_athlete = "{{ $view_data['atlet']['code_data'] }}";
                var hasil_up = $('input[name="new_hasil_{{$id}}"]').val();

                loadingpage(2000);
                $.ajax({
                    type: "POST",
                    url: "saveresult?_token={{csrf_token()}}&token={{$request['token']}}&u={{$request['u']}}",
                    data:{
                        code_data:code_data,
                        code_championship:code_championship,
                        code_event:code_event,
                        code_athlete:code_athlete,
                        hasil_up:hasil_up
                    },
                    cache: false,
                    success: function(res){
                        loadingpage(0);
                        console.log(res.status_message);
                        if(res.status_message == 'error'){
                            $('div[data-model="confirmasi_data"]').modal({backdrop: false});
                            $('div[data-model="confirmasi_data"] .modal-body').html('<div class="alert alert-danger">Data gagal disimpan.</div>');
                            $('button[btn-action="aciton-confirmasi"]').remove();
                            $('input[name="new_hasil_{{$id}}"]').val(hasil_up);
                        }else{
                            $('div[data-model="listproduk"]').modal('hide');
                            $('.bg_act_page_main button').prop({disabled:true});
                            $('[line="list_data"] button').prop({disabled:true});
                            $('[line="list_data"] input').prop({disabled:true});
                            // window.location.reload();
                            window.location.href = "viewresult?d=" + res.code_data + "&code_championship=" + code_championship + "&code_event=" + code_event;

                        }
                    }
                });
            });


        });
    </script>
@empty
    <tr>
        <td style="text-align:center; padding: 20px; background-color: #FFFFFF; cursor: default; font-weight: 600; height: 250px; font-size: 14px;" colspan="20" >
            <i class="fa fa-shopping-bag"></i>
        </td>
    </tr>
    
    <script>
        $(document).ready(function(){
            $('.bg_act_page_main button').prop({disabled:true});
            $('[name="btn_cancel"]').prop({disabled:false});
            $('[onclick="BackPage()"]').prop({disabled:false});
        });
    </script>
@endforelse

@if($no > 0)
    @for ($i = 0; $i <= 0; $i++)
        <tr>
            <td class="blank" style="text-align:center;"></td>
            <td class="blank" style="text-align:center;"></td>
            <td class="blank" style="text-align:center;"></td>
            <td class="blank" style="text-align:center;"></td>
            <td class="blank" style="text-align:center;"></td>
            <td class="blank" style="text-align:center;"></td>
            <td class="blank" style="text-align:center;"></td>
            <td class="blank" style="text-align:center;"></td>
            <td class="blank" style="text-align:center;"></td>
        </tr>
    @endfor
@endif

<script type="text/javascript">
    $(document).ready(function(){
        var hg_td = $('#hg_td').height();
        $('.blank').css({"height":"40px","padding":"18px"});
    });
</script>