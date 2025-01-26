/**
 * __________________________________________________________________
 *
 * Phillipine Address Selector
 * __________________________________________________________________
 *
 * MIT License
 *
 * Copyright (c) 2020 Wilfred V. Pine
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package Phillipine Address Selector
 * @author Wilfred V. Pine <only.master.red@gmail.com>
 * @copyright Copyright 2020 (https://dev.confired.com)
 * @link https://github.com/redmalmon/philippine-address-selector
 * @license https://opensource.org/licenses/MIT MIT License
 */

var my_handlers = {
  // Fill provinces
  fill_provinces: function () {
    var region_code = $(this).val();

    var region_text = $(this).find("option:selected").text();
    $("#region-text").val(region_text);

    $("#province-text").val("");
    $("#city-text").val("");
    $("#barangay-text").val("");

    let dropdown = $("#province");
    dropdown.empty();
    dropdown.append(
      '<option selected="true" disabled>Choose State/Province</option>'
    );
    dropdown.prop("selectedIndex", 0);

    let city = $("#city");
    city.empty();
    city.append('<option selected="true" disabled></option>');
    city.prop("selectedIndex", 0);

    let barangay = $("#barangay");
    barangay.empty();
    barangay.append('<option selected="true" disabled></option>');
    barangay.prop("selectedIndex", 0);

    var url = "../philippine-address-selector-main/ph-json/province.json";
    $.getJSON(url, function (data) {
      var result = data.filter(function (value) {
        return value.region_code == region_code;
      });

      result.sort(function (a, b) {
        return a.province_name.localeCompare(b.province_name);
      });

      $.each(result, function (key, entry) {
        dropdown.append(
          $("<option></option>")
            .attr("value", entry.province_code)
            .text(entry.province_name)
        );
      });
    });
  },
  // Fill cities
  fill_cities: function () {
    var province_code = $(this).val();

    var province_text = $(this).find("option:selected").text();
    $("#province-text").val(province_text);

    $("#city-text").val("");
    $("#barangay-text").val("");

    let dropdown = $("#city");
    dropdown.empty();
    dropdown.append(
      '<option selected="true" disabled>Choose city/municipality</option>'
    );
    dropdown.prop("selectedIndex", 0);

    let barangay = $("#barangay");
    barangay.empty();
    barangay.append('<option selected="true" disabled></option>');
    barangay.prop("selectedIndex", 0);

    var url = "../philippine-address-selector-main/ph-json/city.json";
    $.getJSON(url, function (data) {
      var result = data.filter(function (value) {
        return value.province_code == province_code;
      });

      result.sort(function (a, b) {
        return a.city_name.localeCompare(b.city_name);
      });

      $.each(result, function (key, entry) {
        dropdown.append(
          $("<option></option>")
            .attr("value", entry.city_code)
            .text(entry.city_name)
        );
      });
    });
  },
  // Fill barangays
  fill_barangays: function () {
    var city_code = $(this).val();

    var city_text = $(this).find("option:selected").text();
    $("#city-text").val(city_text);

    $("#barangay-text").val("");

    let dropdown = $("#barangay");
    dropdown.empty();
    dropdown.append(
      '<option selected="true" disabled>Choose barangay</option>'
    );
    dropdown.prop("selectedIndex", 0);

    var url = "../philippine-address-selector-main/ph-json/barangay.json";
    $.getJSON(url, function (data) {
      var result = data.filter(function (value) {
        return value.city_code == city_code;
      });

      result.sort(function (a, b) {
        return a.brgy_name.localeCompare(b.brgy_name);
      });

      $.each(result, function (key, entry) {
        dropdown.append(
          $("<option></option>")
            .attr("value", entry.brgy_code)
            .text(entry.brgy_name)
        );
      });
    });
  },
  onchange_barangay: function () {
    var barangay_text = $(this).find("option:selected").text();
    $("#barangay-text").val(barangay_text);
  },
};

$(function () {
  $("#region").on("change", my_handlers.fill_provinces);
  $("#province").on("change", my_handlers.fill_cities);
  $("#city").on("change", my_handlers.fill_barangays);
  $("#barangay").on("change", my_handlers.onchange_barangay);

  let dropdown = $("#region");
  dropdown.empty();
  dropdown.append('<option selected="true" disabled>Choose Region</option>');
  dropdown.prop("selectedIndex", 0);

  const url = "../philippine-address-selector-main/ph-json/region.json";
  $.getJSON(url, function (data) {
    $.each(data, function (key, entry) {
      dropdown.append(
        $("<option></option>")
          .attr("value", entry.region_code)
          .text(entry.region_name)
      );
    });
  });
});
