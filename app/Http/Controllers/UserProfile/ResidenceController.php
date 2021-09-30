<?php

namespace App\Http\Controllers\UserProfile;

use App\Http\Controllers\Controller;
use App\Helpers\ResponseHelper;
use DB;

class ResidenceController extends Controller{
    
    // Отдаёт список регионов (областей) РФ
    public function get_regions(){
        try{
            $regions = DB::table('regions')->get();
        } catch (Exception $e) {
            return ResponseHelper::response_error_read_db();
        }

        return ResponseHelper::response_ok($regions);
    }

    // Отдаёт список городов в регионе, id которого передаётся в get-запросе.
    public function get_cities($regionId){
        try{
            $cities = DB::table('cities')
                ->select('id', 'cityName')
                ->where('addrRegionId', $regionId)
                ->get();
        } catch (Exception $e) {
            return ResponseHelper::response_error_read_db();
        }

        return ResponseHelper::response_ok($cities);
    }

    // Отдает список мед организаций в выбранном регионе по ид региона из гет запроса
    public function get_works($regionId){
        try{
            $works = DB::table('med_orgs')
                ->select('id','oid', 'nameShort', 'regionId', 'regionName', 'addrRegionName', 'isFederalCity', 'streetName', 
                    'prefixStreet', 'house', 'areaName', 'prefixArea')
                ->where('regionId', $regionId)
                ->get();
        }
        catch (\Exception $e){
            return ResponseHelper::response_error_read_db();
        }

        return ResponseHelper::response_ok($works);
    }

    // отдает список специализаций
    public function get_specializations(){
        try{
            $specializations = DB::table('specializations')->get();
        } catch (Exception $e) {
            return ResponseHelper::response_error_read_db();
        }

        return ResponseHelper::response_ok($specializations);
    }

    // отдает список научных интересов
    public function get_scientific_interests($spec_code = NULL, $add_spec_code = NULL){
        try{
            // общая для всех специальностей область интересов
            $scientific_interests['general'] = DB::table('scientific_interests')
                ->where('specialization_code', NULL)
                ->get();
            // область интересов для выбранной специальности
            if($spec_code !== NULL){
                $scientific_interests[$spec_code] = DB::table('scientific_interests')
                    ->where('specialization_code', $spec_code)
                    ->get();
            }
            // область интересов для выбранной дополнительной специальности
            if($add_spec_code !== NULL){
                $scientific_interests[$add_spec_code] = DB::table('scientific_interests')
                    ->where('specialization_code', $add_spec_code)
                    ->get();
            }
        } catch (Exception $e) {
            return ResponseHelper::response_error_read_db();
        }

        return ResponseHelper::response_ok($scientific_interests);
    }
}
